<?php

namespace App\Corundum\Kit;

use Illuminate\Support\Facades\Storage;
use App\Enums\Image\ImageTypesEnum;
use App\Models\Image;

class Path
{

    /**
     * @param Image $image
     * @param string $disk
     * @param string $type
     *
     * @return string
     * @throws
     */
    public static function physical(Image $image, string $disk = null, string $type = ImageTypesEnum::TYPE_ORIGINAL): string
    {
        return Storage::disk($disk)->get(static::relative($image, $type));
    }

    /**
     * @param Image $image
     * @param string $type
     *
     * @return string
     */
    public static function relative(Image $image, string $type = ImageTypesEnum::TYPE_ORIGINAL): string
    {
        if ($type === ImageTypesEnum::TYPE_ORIGINAL) {
            return static::original($image);
        }

        return static::thumbnail($image);
    }

    /**
     * @param Image $image
     * @return string
     */
    protected static function original(Image $image): string
    {
        // todo: original path
    }

    protected static function thumbnail(Image $image): string
    {
        // todo: thumbnail path
    }

}