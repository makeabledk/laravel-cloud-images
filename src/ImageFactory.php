<?php

namespace Makeable\CloudImages;

use BadMethodCallException;
use Closure;
use Illuminate\Support\Arr;
use Makeable\CloudImages\Contracts\ResponsiveImage;
use Makeable\CloudImages\Contracts\ResponsiveImageVersion;
use Makeable\CloudImages\Exceptions\FailedDownloadException;

class ImageFactory implements ResponsiveImageVersion
{
    use HasDimensions,
        ValueCasting;

    /**
     * @var
     */
    public $url;

    /**
     * @var ResponsiveImage
     */
    protected $image;

    /**
     * @var array
     */
    protected $transformations = [];

    /**
     * @param $url
     */
    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * @param ResponsiveImage $image
     * @return ImageFactory
     */
    public static function make(ResponsiveImage $image)
    {
        $factory = new static($image->url);
        $factory->image = $image;

        return $factory;
    }

    /**
     * @return ImageFactory
     */
    public function clone()
    {
        return clone $this;
    }

    /**
     * @return string
     */
    public function get()
    {
        $options = array_reduce($this->transformations, function ($options, Closure $transformation) {
            return call_user_func($transformation->bindTo($this), $options);
        }, ['sizing' => [], 'extra' => []]);

        // No sizing transformation has been applied (scale, crop etc)
        if (count(Arr::get($options, 'sizing', [])) === 0) {
            // No dimensions has been set whatsoever
            if (count($this->getDimensions()) === 0) {
                return $this->original()->get();
            }

            // Dimensions has been set through setDimensions()
            // - probably through responsive image class
            return $this->scale(...$this->getDimensions())->get();
        }

        return $this->url
            ? rtrim($this->url, '=').'='.collect($options)->flatten()->implode('-')
            : null;
    }

    /**
     * @return ResponsiveImageFactory
     * @throws \Throwable
     */
    public function responsive()
    {
        throw_unless($this->image, BadMethodCallException::class, 'No image instance bound to factory. Use static make() method instead.');

        return new ResponsiveImageFactory($this->image, $this);
    }

    /**
     * @return string
     * @throws \Throwable
     */
    public function getContents()
    {
        $response = app(\GuzzleHttp\Client::class)->request('GET', $this->get());

        throw_unless($response->getStatusCode() === 200, FailedDownloadException::class, 'Failed with status code '.$response->getStatusCode());

        return $response->getBody();
    }

    // _________________________________________________________________________________________________________________

    /**
     * @param int $percentage
     * @return ImageFactory
     */
    public function blur($percentage = 5)
    {
        return $this->param("fSoften=1,{$percentage},0");
    }

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
            ->transform(function ($options) use ($mode) {
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
     * @param $max
     * @return ImageFactory
     */
    public function maxDimension($max)
    {
        return $this
            ->setDimensions($max)
            ->transform(function ($options) {
                return $this->setSizingOption(['s'.$this->getMaxDimension()], $options);
            });
    }

    /**
     * @return ImageFactory
     */
    public function original()
    {
        return $this
            ->setDimensions(null)
            ->transform(function ($options) {
                // Explicit dimensions could have been set via setDimensions()
                // and they may have been set after calling original().
                // Therefore we'll scale the image instead.
                if (count($this->getDimensions()) > 0) {
                    return $this->setSizingOption(['s', 'w'.$this->getWidth(), 'h'.$this->getHeight()], $options);
                }

                // No dimensions has been set whatsoever
                // so we can just return the original
                return $this->setSizingOption(['s0'], $options);
            });
    }

    /**
     * @param $value
     * @return ImageFactory
     */
    public function param($value)
    {
        return $this->transform(function ($options) use ($value) {
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
            ->transform(function ($options) {
                return $this->setSizingOption(['s', 'w'.$this->getWidth(), 'h'.$this->getHeight()], $options);
            });
    }

    // _________________________________________________________________________________________________________________

    /**
     * @return int|mixed
     */
    public function getDisplayWidth()
    {
        return $this->getWidth();
    }

    /**
     * @param Closure $transformation
     * @return $this
     */
    protected function transform(Closure $transformation)
    {
        array_push($this->transformations, $transformation);

        return $this;
    }

    /**
     * @param $option
     * @param $value
     * @param $options
     * @return $options
     */
    protected function addExtraOption($value, $options)
    {
        $options['extra'] = array_merge(Arr::get($options, 'extra', []), Arr::wrap($value));

        return $options;
    }

    /**
     * @param mixed $value
     * @param array $options
     * @return array
     */
    protected function setSizingOption($value, $options)
    {
        $options['sizing'] = $value;

        return $options;
    }
}
