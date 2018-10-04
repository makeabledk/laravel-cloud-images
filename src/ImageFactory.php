<?php

namespace Makeable\CloudImages;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use JsonSerializable;

class ImageFactory implements Arrayable, JsonSerializable
{
    /**
     * @var
     */
    public $url;

    /**
     * @var array|null
     */
    protected $dimensions;

    /**
     * @var array
     */
    protected $mutations = [];

    /**
     * @param $url
     */
    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->get();
    }

    /**
     * @return string
     */
    public function get()
    {
        $options = array_reduce($this->mutations, function ($options, $mutation) {
            return $this->applyMutation($mutation, $options);
        }, ['sizing' => [], 'extra' => []]);

        if (count(array_get($options, 'sizing', [])) === 0) {
            return $this->original()->get();
        }

        return $this->url
            ? rtrim($this->url, '=').'='.collect($options)->flatten()->implode('-')
            : null;
    }

    /**
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @return string
     */
    public function toArray()
    {
        return $this->get();
    }

    // _________________________________________________________________________________________________________________

    /**
     * @return mixed
     */
    public function getWidth()
    {
        return array_get($this->getDimensions(), 0);
    }

    /**
     * @return mixed
     */
    public function getHeight()
    {
        return array_get($this->getDimensions(), 1);
    }

    /**
     * @return mixed
     */
    public function getMaxDimension()
    {
        return max(...$this->getDimensions());
    }

    /**
     * @return array|null
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }

    /**
     * @param $width
     * @param null $height
     * @return $this
     */
    public function setDimensions($width, $height = null)
    {
        $this->dimensions = [$width, $height];

        return $this;
    }

    // _________________________________________________________________________________________________________________

    /**
     * @param $width
     * @param $height
     * @param string $mode
     * @return ImageFactory
     */
    public function crop($width, $height, $mode = 'c')
    {
        return $this
            ->setDimensions($width, $height)
            ->addMutation(function ($options) use ($mode) {
                return $this->setSizingOption([$mode, 'w'.$this->getWidth(), 'h'.$this->getHeight()], $options);
            });
    }

    /**
     * @param $width
     * @param $height
     * @return ImageFactory
     */
    public function cropCenter($width, $height)
    {
        return $this->crop($width, $height, 'n');
    }

    /**
     * @return ImageFactory
     */
    public function original()
    {
        return $this
            ->setDimensions(null)
            ->addMutation(function ($options) {
                return $this->setSizingOption(['s0'], $options);
            });
    }

    /**
     * @param $max
     * @return ImageFactory
     */
    public function maxDimension($max)
    {
        return $this
            ->setDimensions($max)
            ->addMutation(function ($options) {
                return $this->setSizingOption(['s'.$this->getMaxDimension()], $options);
            });
    }

    /**
     * @param $value
     * @return ImageFactory
     */
    public function param($value)
    {
        return $this->addMutation(function ($options) use ($value) {
            return $this->addExtraOption($value, $options);
        });
    }

    /**
     * @param $width
     * @param $height
     * @return ImageFactory
     */
    public function scale($width, $height)
    {
        return $this
            ->setDimensions($width, $height)
            ->addMutation(function ($options) {
                return $this->setSizingOption(['s', 'w'.$this->getWidth(), 'h'.$this->getHeight()], $options);
            });
    }

    // _________________________________________________________________________________________________________________

    /**
     * @param callable $mutation
     * @return $this
     */
    protected function addMutation($mutation)
    {
        array_push($this->mutations, $mutation);

        return $this;
    }

    /**
     * @param $mutation
     * @param $options
     * @return mixed
     */
    protected function applyMutation($mutation, $options)
    {
        return call_user_func($mutation, $options);
    }

    /**
     * @param $option
     * @param $value
     * @param $options
     * @return $options
     */
    protected function addExtraOption($value, $options)
    {
        $options['extra'] = array_merge(array_get($options, 'extra', []), Arr::wrap($value));

        return $options;
    }

    /**
     * @param $option
     * @param $value
     * @param $options
     * @return $options
     */
    protected function setSizingOption($value, $options)
    {
        $options['sizing'] = $value;

        return $options;
    }
}
