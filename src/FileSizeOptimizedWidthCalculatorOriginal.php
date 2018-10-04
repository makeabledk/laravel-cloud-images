<?php

namespace Makeable\CloudImages;

use Illuminate\Support\Collection;
use Makeable\CloudImages\ResponsiveImages\WidthCalculator\WidthCalculator;

class FileSizeOptimizedWidthCalculatorOriginal implements WidthCalculator
{
    /**
     * @param string $imagePath
     * @return Collection
     */
    public function calculateWidthsFromFile(string $imagePath): Collection
    {
        $image = ImageFactory::load($imagePath);

        $width = $image->getWidth();
        $height = $image->getHeight();
        $fileSize = filesize($imagePath);

        return $this->calculateWidths($fileSize, $width, $height);
    }

    /**
     * @param int $fileSize
     * @param int $width
     * @param int $height
     * @return Collection
     */
    public function calculateWidths(int $fileSize, int $width, int $height): Collection
    {
        $targetWidths = collect();

        $targetWidths->push($width);

        $ratio = $height / $width;
        $area = $height * $width;

        $predictedFileSize = $fileSize;
        $pixelPrice = $predictedFileSize / $area;

        while (true) {
            $predictedFileSize *= 0.7;

            $newWidth = (int) floor(sqrt(($predictedFileSize / $pixelPrice) / $ratio));

            if ($this->finishedCalculating($predictedFileSize, $newWidth)) {
                return $targetWidths;
            }

            $targetWidths->push($newWidth);
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
