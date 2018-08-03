<?php

namespace App\Jobs;

use App\Corundum\Adapter;
use App\Corundum\Kit\ImagePath;
use App\Enums\Image\ImageStatusEnum;
use App\Enums\Image\ImageViewsEnum;
use App\Enums\Queue\QueueEnum;
use App\Models\Bucket;
use App\Models\Image;
use App\Models\View;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImageProcessing implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var array
     */
    protected $adapters = [
        ImageViewsEnum::CONTAIN => \App\Corundum\Adapters\Contain::class,
        ImageViewsEnum::COVER => \App\Corundum\Adapters\Cover::class,
        ImageViewsEnum::FIT => \App\Corundum\Adapters\Fit::class,
        ImageViewsEnum::NONE => \App\Corundum\Adapters\None::class,
        ImageViewsEnum::RESIZE => \App\Corundum\Adapters\Resize::class,
    ];

    /**
     * @var Image
     */
    protected $image;

    /**
     * @var Bucket
     */
    protected $bucket;

    /**
     * Принудительная генерация изображений
     *
     * @var bool
     */
    protected $force;

    /**
     * ImageProcessing constructor.
     *
     * @param Image $image
     * @param Bucket $bucket
     * @param bool $force
     */
    public function __construct(Image $image, Bucket $bucket, bool $force = false)
    {
        $this->queue = QueueEnum::PROCESSING;
        $this->image = $image;
        $this->bucket = $bucket;
        $this->force = $force;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        if (!ImagePath::exists($this->image, $this->bucket)) {
            Log::error('The original image was deleted', $this->image->toArray());
            $this->failed();
            return;
        }

        if (!$this->force && $this->image->status === ImageStatusEnum::PROCESSING) {
            Log::info('The image is already in process', $this->image->toArray());
            return;
        }

        $states = [ImageStatusEnum::UPLOADED];
        if (\in_array($this->image->status, $states, true)) {
            $this->image->status = ImageStatusEnum::PROCESSING;
            $this->image->save();
        }

        /**
         * Ставим задачу на получение метаданных
         */
        dispatch(new ImageMetadata($this->image, $this->bucket));

        /**
         * загружаем корзину
         */
        $this->image->load(Image::REL_BUCKET);

        /**
         * Генерируем представления
         */
        foreach ($this->image->views as $view) {
            $this->processing($view);
        }

        /**
         * Завершено
         */
        $this->image->update(['status' => ImageStatusEnum::FINISHED]);
    }

    /**
     * Процесс генерации миниатюр
     *
     * @param View $view
     */
    protected function processing(View $view): void
    {
        $physical = ImagePath::physical($this->image, $this->bucket);
        $thumbnail = ImagePath::physical($this->image, $this->bucket, $view->name);

        /**
         * Файл принудительно генерировать не нужно и
         *  он уже существует
         */
        if (!$this->force && File::exists($thumbnail)) {
            /**
             * Файл уже существует
             */
            return;
        }

        /**
         * создаем директорию
         */
        ImagePath::makeDirectory($this->image, $view->name);

        /**
         * @var Adapter $adapter
         */
        $adapter = new $this->adapters[$view->type]($physical);
        $adapter->apply($view->toArray())
            ->save($thumbnail, $view->quality);

        dispatch(new ImageOptimize($this->image, $this->bucket, $view));
    }

    /**
     * @return void
     */
    protected function failed(): void
    {
        dispatch(new ImageFailed($this->image, $this->bucket));
    }

}
