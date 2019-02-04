<?php

namespace Makeable\CloudImages\Contracts;

use Makeable\CloudImages\ImageFactory;
use Makeable\CloudImages\TinyPlaceholder;

/**
 * @property string $width
 * @property string $height
 * @property string $size
 * @property string $tiny_placeholder
 */
interface ResponsiveImage
{
    /**
     * @return TinyPlaceholder
     */
    public function placeholder();

    /**
     * @return array
     */
    public function getDimensions();

    /**
     * @return ImageFactory
     */
    public function make();
}
