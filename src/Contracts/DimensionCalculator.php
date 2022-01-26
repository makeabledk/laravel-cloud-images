<?php

namespace Makeable\CloudImages\Contracts;

use Illuminate\Support\Collection;

interface DimensionCalculator
{
    /**
     * @param $originalWidth
     * @param $originalHeight
     * @param $originalSize
     */
    public function __construct($originalWidth, $originalHeight, $originalSize);

    /**
     * @param  int  $width
     * @param  int  $height
     * @return Collection
     */
    public function calculateDimensions($width, $height): Collection;
}
