<?php

namespace Makeable\CloudImages;

use Makeable\CloudImages\Contracts\ResponsiveImage;
use Makeable\CloudImages\Contracts\ResponsiveImageVersion;

class TinyPlaceholder implements ResponsiveImageVersion
{
    use HasDimensions,
        ValueCasting;

    /**
     * @var ResponsiveImage
     */
    protected $image;

    /**
     * @param ResponsiveImage $image
     */
    public function __construct(ResponsiveImage $image)
    {
        $this->image = $image;
    }

    /**
     * @return ImageFactory
     */
    public function factory()
    {
        return $this->image->make()->maxDimension(32)->blur();
    }

    /**
     * @return string
     * @throws \Throwable
     */
    public function create()
    {
        return 'data:image/jpeg;base64,'.base64_encode($this->factory()->getContents());
    }

    /**
     * @return string
     */
    public function get()
    {
        // We need to resolve the normalized dimensions
        // as the dimensions may have been specified
        // explicitly using setDimensions()
        list($width, $height) = $this->getNormalizedDimensions(...$this->image->getDimensions());

        $svg = view('cloud-images::placeholder-svg', [
            'originalImageWidth' => $width,
            'originalImageHeight' => $height,
            'tinyImageBase64' => $this->image->tiny_placeholder,
        ]);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Fetch the actual width of the placeholder through
     * the factory method that originally created it.
     *
     * @return int|mixed
     */
    public function getDisplayWidth()
    {
        return array_get($this->factory()->getNormalizedDimensions(...$this->image->getDimensions()), 0);
    }
}
