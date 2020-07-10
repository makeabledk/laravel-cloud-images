<?php

namespace Makeable\CloudImages\Nova\Fields;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Image as ImageField;
use Makeable\CloudImages\Image;

class CloudImage extends ImageField
{
    /**
     * @param string $name
     * @param string|null $attribute
     * @return void
     */
    public function __construct($name, $attribute = null)
    {
        parent::__construct($name, $attribute);

        // Until we implement the download() properly
        $this->downloadResponseCallback = null;

        $this->resolveUsing(function ($image) {
            return $image->exists ? $image : null;
        });

        $this->thumbnail(function () {
            return $this->value ? $this->value->make()->maxDimension(50)->get() : null;
        });

        $this->preview(function () {
            return $this->value ? $this->value->make()->maxDimension(400)->get() : null;
        });

        $this->store(function (Request $request, Model $model) {
            return function () use ($request, $model) {
                $model->{$this->attribute}->replaceWith(Image::upload($request->file($this->attribute)));
            };
        });

        $this->delete(function (Request $request, $model) {
            $model->{$this->attribute}->delete();

            return true;
        });
    }
}
