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
     * @var array
     */
    protected $options = [
        'sizing' => [],
        'custom' => [],
    ];

    /**
     * CloudImage constructor.
     *
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
        if (count($this->options['sizing']) === 0) {
            $this->original();
        }

        return $this->url
            ? rtrim($this->url, '=').'='.collect($this->options)->flatten()->implode('-')
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
     * @param $width
     * @param $height
     * @param string $mode
     * @return ImageFactory
     */
    public function crop($width, $height, $mode = 'c')
    {
        return $this->setGroup('sizing', [$mode, 'w'.$width, 'h'.$height]);
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
        return $this->setGroup('sizing', ['s0']);
    }

    /**
     * @param $max
     * @return ImageFactory
     */
    public function maxDimension($max)
    {
        return $this->setGroup('sizing', ['s'.$max]);
    }

    /**
     * @param $param
     * @return ImageFactory
     */
    public function param($param)
    {
        return $this->setGroup('custom',
            array_merge($this->options['custom'], Arr::wrap($param))
        );
    }

    /**
     * @param $width
     * @param $height
     * @return ImageFactory
     */
    public function scale($width, $height)
    {
        return $this->setGroup('sizing', ['s', 'w'.$width, 'h'.$height]);
    }

    /**
     * @param $option
     * @param $value
     * @return $this
     */
    public function setGroup($option, $value)
    {
        $this->options[$option] = $value;

        return $this;
    }
}
