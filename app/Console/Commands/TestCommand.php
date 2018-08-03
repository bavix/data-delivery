<?php

namespace App\Console\Commands;

use App\Enums\Image\ImageStatusEnum;
use App\Enums\Image\ImageViewsEnum;
use App\Jobs\ImageReprocessing;
use App\Models\Bucket;
use App\Models\Image;
use App\Models\View;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;

class TestCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Command';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $time = Carbon::now()->subMinute();
        dispatch(new ImageReprocessing($time));
        die;

        $bucket = Bucket::query()->first();
        if (!$bucket) {
            $bucket = new Bucket();
            $bucket->name = 'test';
            $bucket->save();
        }
        
        $userId = 1;

        if (View::query()->count() < 3) {
            $view = new View();
            $view->type = ImageViewsEnum::FIT;
            $view->name = 'xs-fit';
            $view->height = 250;
            $view->width = 250;
            $view->quality = 75;
            $view->webp = 1;
            $view->user_id = $userId;
            $view->bucket_id = $userId;
            $view->save();

            $view = new View();
            $view->type = ImageViewsEnum::CONTAIN;
            $view->name = 'xs-contain';
            $view->height = 250;
            $view->width = 250;
            $view->quality = 75;
            $view->webp = 1;
            $view->user_id = $userId;
            $view->bucket_id = $userId;
            $view->save();

            $view = new View();
            $view->type = ImageViewsEnum::COVER;
            $view->name = 'xs-cover';
            $view->height = 250;
            $view->width = 250;
            $view->quality = 75;
            $view->webp = 1;
            $view->user_id = $userId;
            $view->bucket_id = $userId;
            $view->save();
        }

//        $image = Image::query()->first();
//
//        if (!$image) {
//            $image = new Image();
//            $image->bucket_id = $bucket->id;
//            $image->user_id = $userId;
//            $image->save();
//        }
//
//        var_dump($image->toArray());
        die;
    }

}
