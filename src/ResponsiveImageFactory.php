<?php

namespace Makeable\CloudImages;

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
     */
    public function __construct(Image $image)
    {
        $this->image = $image;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->factory->$name(...$arguments);
    }

    public function get()
    {

    }
}