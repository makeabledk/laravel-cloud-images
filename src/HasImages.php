<?php

namespace Makeable\CloudImages;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasImages
{
    /**
     * @return MorphToMany
     */
    public function images()
    {
        return $this->morphToMany(Image::class, 'attachment');
    }
}