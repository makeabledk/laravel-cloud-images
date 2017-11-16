<?php

namespace Makeable\CloudImages\Tests\Stubs;

use Makeable\CloudImages\Image;

class ProductImage extends Image
{
    public function getThumbnailAttribute()
    {
        return $this->make()->cropCenter(400, 300)->get();
    }
}
