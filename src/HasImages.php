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
        return $this->morphToSortedMany($this->getImageModelClass(), 'attachable', 'order', 'image_attachments', 'attachable_id', 'image_id')
            ->withPivot('tag')->withTimestamps();
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
            ->first() ?: (new $this->getImageModelClass())->reserveFor($this, $tag);
    }
    
    /**
     * @return string
     */
    protected function getImageModelClass()
    {
        return property_exists($this, 'useImageModel') ? $this->useImageModel : Image::class;
    }
}
