<?php

namespace App\Corundum;

use Intervention\Image\Image;

interface DriverInterface
{

    /**
     * DriverInterface constructor.
     *
     * @param string $path
     */
    public function __construct(string $path);

    /**
     * @param array $data
     *
     * @return Image
     */
    public function apply(array $data): Image;

}
