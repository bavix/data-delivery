<?php

namespace App\Corundum;

use Illuminate\Support\Arr;
use Intervention\Image\Facades\Image as ImageManager;
use Intervention\Image\Image;

abstract class Adapter implements DriverInterface
{

    /**
     * @var Image
     */
    protected $image;

    /**
     * @var string
     */
    protected $path;

    /**
     * Adapter constructor.
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * @param Image $image
     * @param array $config
     * @param bool $minimal
     *
     * @return array
     */
    protected function received(Image $image, array $config, $minimal = true): array
    {
        $width = (int)Arr::get($config, 'width');
        $height = (int)Arr::get($config, 'height');

        $shiftWidth = 0;
        $shiftHeight = 0;

        $_width = $width;
        $_height = $height;

        if ($image->height() < $image->width()) {
            $_height = $image->height() * $width / $image->width();
            $shiftHeight = ($_height - $height) / 2;
        } else {
            $_width = $image->width() * $height / $image->height();
            $shiftWidth = ($_width - $width) / 2;
        }

        if ($minimal ^ $_width > $width) {
            $_height = $_height * $width / $_width;
            $_width = $width;
        }

        if ($minimal ^ $_height > $height) {
            $_width = $_width * $height / $_height;
            $_height = $height;
        }

        return [
            'config' => [
                'width' => $width,
                'height' => $height,
            ],
            'received' => [
                'width' => (int)$_width,
                'height' => (int)$_height,
            ],
            'shifted' => [
                'width' => (int)$shiftWidth,
                'height' => (int)$shiftHeight,
            ]
        ];
    }

    /**
     * @param Image $image
     * @param array $data
     * @param string $color
     *
     * @return Image
     */
    protected function handler(Image $image, array $data, string $color = null): Image
    {
        $color = $color ?: 'rgba(0, 0, 0, 0)';

        $image->resize(
            Arr::get($data, 'received.width'),
            Arr::get($data, 'received.height')
        );

        $image->resizeCanvas(
            $width = Arr::get($data, 'config.width'),
            $height = Arr::get($data, 'config.height'),
            'center',
            false,
            $color
        );

        $fill = ImageManager::canvas($width, $height, $color);

        if ($this->image->getDriver() instanceof \Intervention\Image\Gd\Driver) {
            $object = $fill;
            $fill = $image;
            $image = $object;
        }

        return $fill->fill(
            $image,
            Arr::get($data, 'shifted.width'),
            Arr::get($data, 'shifted.height')
        );
    }

    /**
     * @return Image
     */
    protected function image(): Image
    {
        if (!$this->image) {
            $this->image = ImageManager::make($this->path);
        }

        return $this->image;
    }

}
