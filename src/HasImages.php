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
        return $this->morphToSortedMany(config('cloud-images.model'), 'attachable', 'order', 'image_attachments')
            ->withPivot('tag');
    }

    /**
     * @param null $tag
     * @return Image
     */
    public function image($tag = null)
    {
        return $this->images
            ->when($tag !== null, function ($images) use ($tag) {
                return $images->where('pivot.tag', $tag);
            })
            ->first() ?: (new Image)->reserveFor($this, $tag);
    }
}
