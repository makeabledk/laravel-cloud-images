<?php

namespace Makeable\CloudImages;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

class CloudImage implements Arrayable
{
    /**
     * @var
     */
    public $url;

    /**
     * @var
     */
    public $filename;

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
     * @param $filename
     */
    public function __construct($url, $filename)
    {
        $this->url = $url;
        $this->filename = $filename;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getUrl();
    }

    /**
     * @param File|UploadedFile $image
     * @param string|null $filename
     * @return CloudImage
     */
    public static function upload($image, $filename = null)
    {
        return resolve(\Makeable\CloudImages\Client::class)->upload($image, $filename);
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        if (count($this->options['sizing']) === 0) {
            $this->original();
        }

        return $this->url.collect($this->options)->flatten()->implode('-');
    }

    /**
     * @return string
     */
    public function toArray()
    {
        return $this->getUrl();
    }

    // _________________________________________________________________________________________________________________

    /**
     * @param $width
     * @param $height
     * @param string $mode
     * @return CloudImage
     */
    public function crop($width, $height, $mode = 'c')
    {
        return $this->setGroup('sizing', [$mode, 'w'.$width, 'h'.$height]);
    }

    /**
     * @param $width
     * @param $height
     * @return CloudImage
     */
    public function cropCenter($width, $height)
    {
        return $this->crop($width, $height, 'n');
    }

    /**
     * @return CloudImage
     */
    public function original()
    {
        return $this->setGroup('sizing', ['s0']);
    }

    /**
     * @param $max
     * @return CloudImage
     */
    public function maxDimension($max)
    {
        return $this->setGroup('sizing', ['s'.$max]);
    }

    /**
     * @param $param
     * @return CloudImage
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
     * @return CloudImage
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
