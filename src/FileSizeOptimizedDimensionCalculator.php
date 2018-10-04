<?php

namespace Makeable\CloudImages;

use Illuminate\Support\Collection;
use Makeable\CloudImages\ResponsiveImages\WidthCalculator\WidthCalculator;

class FileSizeOptimizedDimensionCalculator // implements WidthCalculator
{
    /**
     * @var mixed
     */
    protected $pixelPrice;

    /**
     * @param $originalWidth
     * @param $originalHeight
     * @param $originalFileSize
     */
    public function __construct($originalWidth, $originalHeight, $originalFileSize)
    {
        $this->pixelPrice = $originalFileSize / ($originalWidth * $originalHeight);
    }

    /**
     * @param Image $image
     * @return $this
     */
    public static function fromImage($image)
    {
        return new static($image->width, $image->height, $image->size);
    }

//
//    /**
//     * @param string $imagePath
//     * @return Collection
//     */
//    public function calculateWidthsFromFile(string $imagePath): Collection
//    {
//        $image = ImageFactory::load($imagePath);
//
//        $width = $image->getWidth();
//        $height = $image->getHeight();
//        $fileSize = filesize($imagePath);
//
//        return $this->calculateWidths($fileSize, $width, $height);
//    }

    /**
     * @param int $width
     * @param int $height
     * @return Collection
     */
    public function calculateDimensions($width, $height)
    {
        $targetSizes = collect();
        $targetSizes->push([$width, $height]);

        $ratio = $height / $width;
        $predictedFileSize = $width * $height * $this->pixelPrice;

        while (true) {
            $predictedFileSize *= 0.7;

            $newWidth = (int) floor(sqrt(($predictedFileSize / $this->pixelPrice) / $ratio));
            $newHeight = (int) floor($newWidth * $ratio);

            if ($this->finishedCalculating($predictedFileSize, $newWidth)) {
                return $targetSizes;
            }

            $targetSizes->push([$newWidth, $newHeight]);
        }
    }

    /**
     * @param int $predictedFileSize
     * @param int $newWidth
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
