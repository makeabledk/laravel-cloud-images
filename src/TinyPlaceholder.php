<?php

namespace Makeable\CloudImages;

use Makeable\CloudImages\Contracts\ResponsiveImageVersion;

class TinyPlaceholder implements ResponsiveImageVersion
{
    use ValueCasting;

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
     * @return string
     * @throws \Throwable
     */
    public function generate()
    {
        $contents = $this->image->make()->maxDimension(32)->blur()->getContents();

        $svg = view('cloud-images::placeholderSvg', [
            'originalImageWidth' => $this->image->width,
            'originalImageHeight' => $this->image->height,
            'tinyImageBase64' => 'data:image/jpeg;base64,'.base64_encode($contents),
        ]);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * @return string
     */
    function get()
    {
        return $this->image->tiny_placeholder;
    }

    /**
     * Always 1px as this is the value used for <img srcset>
     *
     * @return int
     */
    public function getWidth()
    {
        return 1;
    }
}