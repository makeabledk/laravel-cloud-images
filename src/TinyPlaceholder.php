<?php

namespace Makeable\CloudImages;

use Illuminate\Support\Arr;
use Makeable\CloudImages\Contracts\ResponsiveImageVersion;

class TinyPlaceholder implements ResponsiveImageVersion
{
    use HasDimensions,
        ValueCasting;

    /**
     * @var Image
     */
    protected $image;

    /**
     * @param Image $image
     */
    public function __construct(Image $image)
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
        list ($width, $height) = $this->getNormalizedDimensions(...$this->image->getDimensions());

        $svg = view('cloud-images::placeholder_svg', [
            'originalImageWidth' => $width,
            'originalImageHeight' => $height,
            'tinyImageBase64' => $this->image->tiny_placeholder,
        ]);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Get the calculated width from the placeholder
     * factory using the actual image aspect ratio
     *
     * @return int|mixed
     */
    public function getDisplayWidth()
    {
        return array_get($this->factory()->getNormalizedDimensions(...$this->image->getDimensions()), 0);
    }
}
