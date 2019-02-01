<?php

namespace Makeable\CloudImages\Contracts;

use Makeable\CloudImages\ImageFactory;
use Makeable\CloudImages\TinyPlaceholder;

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
