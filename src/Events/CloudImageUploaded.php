<?php

namespace Makeable\CloudImages\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Makeable\CloudImages\ImageFactory;

class CloudImageUploaded
{
    use Dispatchable, SerializesModels;

    /**
     * @var string
     */
    public $path;

    /**
     * @var string
     */
    public $url;

    /**
     * CloudImageUploaded constructor.
     *
     * @param $path
     * @param $url
     */
    public function __construct($path, $url)
    {
        $this->path = $path;
        $this->url = $url;
    }

    /**
     * @return ImageFactory
     */
    public function make()
    {
        return new ImageFactory($this->url);
    }
}