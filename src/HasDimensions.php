<?php

namespace Makeable\CloudImages;

use Illuminate\Support\Arr;

trait HasDimensions
{
    /**
     * @var array|null
     */
    protected $dimensions;

    /**
     * @return array
     */
    public function getDimensions()
    {
        return array_filter(Arr::wrap($this->dimensions));
    }

    /**
     * @return mixed
     */
    public function getHeight()
    {
        return array_get($this->getDimensions(), 1);
    }

    /**
     * @return mixed
     */
    public function getMaxDimension()
    {
        return max(0, ...$this->getDimensions());
    }

    /**
     * Normalize whatever dimensions are given according to the original
     * image. This way we can calculate a 'max dimension' to an actual
     * set of width and height. Defaults to original dimensions.
     *
     * @param $originalWidth
     * @param $originalHeight
     * @return array
     */
    public function getNormalizedDimensions($originalWidth, $originalHeight)
    {
        $aspectRatio = $originalWidth / $originalHeight;

        // No dimensions specified - default to original
        if (count($dimensions = $this->getDimensions()) === 0) {
            return [$originalWidth, $originalHeight];
        }

        // Max dimension specified - convert to actual dimensions
        if (count($dimensions) === 1) {
            return $originalWidth >= $originalHeight
                ? [$width = $dimensions[0], $width / $aspectRatio] // ie. 16:9
                : [($height = $dimensions[0]) * $originalHeight, $height]; // ie. 9:16
        }

        return $dimensions;
    }

    /**
     * @return mixed
     */
    public function getWidth()
    {
        return array_get($this->getDimensions(), 0);
    }

    /**
     * @param $width
     * @param null $height
     * @return $this
     */
    public function setDimensions($width, $height = null)
    {
        $this->dimensions = [$width, $height];

        return $this;
    }
}
