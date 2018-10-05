<?php

namespace Makeable\CloudImages;

use BadMethodCallException;
use Closure;
use Illuminate\Support\Arr;
use Makeable\CloudImages\Contracts\ResponsiveImageVersion;
use Makeable\CloudImages\Exceptions\FailedDownloadException;

class ImageFactory implements ResponsiveImageVersion
{
    use ValueCasting;

    /**
     * @var
     */
    public $url;

    /**
     * @var Image
     */
    protected $image;

    /**
     * @var array|null
     */
    protected $dimensions;

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
     * @param Image $image
     * @return ImageFactory
     */
    public static function make(Image $image)
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

        if (count(array_get($options, 'sizing', [])) === 0) {
            return $this->original()->get();
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
        return max(0, ...$this->getDimensions());
    }

    /**
     * @return array
     */
    public function getDimensions()
    {
        return array_filter(Arr::wrap($this->dimensions));
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
        $options['extra'] = array_merge(array_get($options, 'extra', []), Arr::wrap($value));

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
