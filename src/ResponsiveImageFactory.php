<?php

namespace Makeable\CloudImages;

use Illuminate\Support\Collection;
use Makeable\CloudImages\Contracts\DimensionCalculator;

/**
 * @mixin ImageFactory
 */
class ResponsiveImageFactory
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
     * @param Image $image
     * @param ImageFactory|null $factory
     */
    public function __construct(Image $image, ImageFactory $factory = null)
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

        $fullDimensions = $this->factory->getNormalizedDimensions(...$this->image->getDimensions());

        return $calculator
            ->calculateDimensions(...$fullDimensions)
            ->map(function ($dimensions) {
                return $this->factory->clone()->setDimensions(...$dimensions);
            })
            ->when($this->usingPlaceholder(), function (Collection $versions) use ($fullDimensions) {
                return $versions->push($this->image->placeholder()->setDimensions(...$fullDimensions));
            });
    }

    /**
     * @return bool
     */
    protected function usingPlaceholder()
    {
        return config('cloud-images.use_tiny_placeholders') && $this->image->tiny_placeholder;
    }
}
