<?php

namespace Makeable\CloudImages;

use Illuminate\Support\Collection;

/**
 * @mixin ImageFactory
 */
class ResponsiveImageFactory
{
    /**
     * @var Image
     */
    protected $image;

    /**
     * @var ImageFactory
     */
    protected $factory;

    /**
     * @param Image $image
     * @param ImageFactory|null $factory
     */
    public function __construct(Image $image, ImageFactory $factory = null)
    {
        $this->image = $image;
        $this->factory = $factory ?: $image->make();
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $this->factory->$name(...$arguments);

        return $this;
    }

    /**
     * @return Collection
     */
    public function get()
    {
        $calculator = app()->make(FileSizeOptimizedDimensionCalculator::class, [
            'originalWidth' => $this->image->width,
            'originalHeight' => $this->image->height,
            'originalSize' => $this->image->size,
        ]);

        return $calculator
            ->calculateDimensions(...$this->getNormalizedDimensions())
            ->map(function ($dimensions) {
                return $this->factory->clone()->setDimensions(...$dimensions);
            });
    }

    /**
     * @return array
     */
    protected function getNormalizedDimensions()
    {
        if (count($dimensions = $this->factory->getDimensions()) === 0) { // original
            return $this->image->getDimensions();
        } elseif (count($dimensions) === 1) { // max dimension specified - convert to actual dimensions
            if (max($this->image->getDimensions()) === $this->image->width) {
                return [$width = $dimensions[0], $width / $this->image->aspect_ratio]; // ie. 16:9
            }

            return [($height = $dimensions[0]) * $this->image->aspect_ratio, $height]; // ie. 9:16
        }

        return $dimensions;
    }
}
