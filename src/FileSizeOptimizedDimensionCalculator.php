<?php

namespace Makeable\CloudImages;

use Illuminate\Support\Collection;
use Makeable\CloudImages\Contracts\DimensionCalculator;

class FileSizeOptimizedDimensionCalculator implements DimensionCalculator
{
    /**
     * @var mixed
     */
    protected $pixelPrice;

    /**
     * @param  $originalWidth
     * @param  $originalHeight
     * @param  $originalSize
     */
    public function __construct($originalWidth, $originalHeight, $originalSize)
    {
        $this->pixelPrice = $originalSize / ($originalWidth * $originalHeight);
    }

    /**
     * @param  int  $width
     * @param  int  $height
     * @return Collection
     */
    public function calculateDimensions($width, $height): Collection
    {
        $targetSizes = collect();
        $targetSizes->push([$width, $height]);

        $ratio = $height / $width;
        $predictedFileSize = $width * $height * $this->pixelPrice;

        while (true) {
            $predictedFileSize *= 0.7;

            $newWidth = (int) floor(sqrt(($predictedFileSize / $this->pixelPrice) / $ratio));
            $newHeight = (int) floor($newWidth * $ratio);

            if ($this->finishedCalculating(round($predictedFileSize), $newWidth)) {
                return $targetSizes;
            }

            $targetSizes->push([$newWidth, $newHeight]);
        }
    }

    /**
     * @param  int  $predictedFileSize
     * @param  int  $newWidth
     * @return bool
     */
    protected function finishedCalculating(int $predictedFileSize, int $newWidth): bool
    {
        if ($newWidth < 20) {
            return true;
        }

        if ($predictedFileSize < (1024 * 10)) {
            return true;
        }

        return false;
    }
}
