<?php

namespace Makeable\CloudImages;

use Rutorika\Sortable\MorphToSortedMany;
use Rutorika\Sortable\MorphToSortedManyTrait;

trait HasImages
{
    use MorphToSortedManyTrait;

    /**
     * @return MorphToSortedMany
     */
    public function images()
    {
        return $this->morphToSortedMany(config('cloud-images.model'), 'attachable', 'order', 'image_attachments');
    }

    /**
     * @return Image
     */
    public function image()
    {
        return $this->images->first() ?: new Image;
    }
}
