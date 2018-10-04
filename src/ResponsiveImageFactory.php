<?php

namespace Makeable\CloudImages;

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
    protected $builder;

    /**
     * @param Image $image
     */
    public function __construct(Image $image)
    {
        $this->image = $image;
        $this->builder = $image->make();
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->builder->$name(...$arguments);
    }

    public function get()
    {
        $calculator = app()->makeWith(FileSizeOptimizedDimensionCalculator::class, [
            $this->image->width,
            $this->image->height,
            $this->image->size,
        ]);

        $calculator->calculateDimensions(...$this->getNormalizedDimensions());
    }

    /**
     * @return array
     */
    protected function getNormalizedDimensions()
    {
        if (count($dimensions = $this->builder->getDimensions()) === 0) { // original
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
