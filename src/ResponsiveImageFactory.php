<?php

namespace Makeable\CloudImages;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use JsonSerializable;
use Makeable\CloudImages\Contracts\DimensionCalculator;
use Makeable\CloudImages\Contracts\ResponsiveImage;
use Makeable\CloudImages\Contracts\ResponsiveImageVersion;

/**
 * @mixin ImageFactory
 */
class ResponsiveImageFactory implements Arrayable, JsonSerializable
{
    /**
     * @var Image
     */
    protected $image;

    /**
     * @var ImageFactory
     */
    protected $factory;

    /**
     * ResponsiveImageFactory constructor.
     * @param ResponsiveImage $image
     * @param ImageFactory|null $factory
     */
    public function __construct(ResponsiveImage $image, ImageFactory $factory = null)
    {
        $this->image = $image;
        $this->factory = $factory ?: $image->make();
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $this->factory->$name(...$arguments);

        return $this;
    }

    /**
     * @return Collection
     */
    public function get()
    {
        $calculator = app()->make(DimensionCalculator::class, [
            'originalWidth' => $this->image->width,
            'originalHeight' => $this->image->height,
            'originalSize' => $this->image->size,
        ]);

        return $calculator
            ->calculateDimensions(...$fullDimensions = $this->getDimensions())
            ->map(function ($dimensions) {
                return $this->factory->clone()->setDimensions(...$dimensions);
            })
            ->when($this->usingPlaceholder(), function (Collection $versions) use ($fullDimensions) {
                return $versions->push($this->image->placeholder()->setDimensions(...$fullDimensions));
            });
    }

    /**
     * @return array
     */
    public function getDimensions()
    {
        return $this->factory->getNormalizedDimensions(...$this->image->getDimensions());
    }

    /**
     * @param array $attributes
     * @return string
     */
    public function getHtml(array $attributes = [])
    {
        $view = $this->usingPlaceholder()
            ? 'cloud-images::responsive-image-with-placeholder'
            : 'cloud-images::responsive-image';

        return view($view, array_merge($this->toArray(), [
            'attributeString' => $this->attributesToString($attributes),
        ]))->render();
    }

    /**
     * @return string
     */
    public function getSrc()
    {
        return $this->factory->get();
    }

    /**
     * @return string
     */
    public function getSrcset()
    {
        return $this->get()->map(function (ResponsiveImageVersion $version) {
            return $version->get().' '.$version->getDisplayWidth().'w';
        })->implode(', ');
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'src' => $this->getSrc(),
            'srcset' => $this->getSrcset(),
            'width' => array_get($this->getDimensions(), 0),
        ];
    }

    /**
     * @return bool
     */
    protected function usingPlaceholder()
    {
        return config('cloud-images.use_tiny_placeholders') && $this->image->tiny_placeholder;
    }

    /**
     * @param array $attributes
     * @return string
     */
    protected function attributesToString(array $attributes)
    {
        $attributeString = collect($attributes)->map(function ($value, $name) {
            return $name.'="'.$value.'"';
        })->implode(' ');

        return strlen($attributeString)
            ? ' '.$attributeString
            : '';
    }
}
