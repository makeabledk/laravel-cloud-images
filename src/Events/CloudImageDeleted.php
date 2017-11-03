<?php

namespace Makeable\CloudImages\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CloudImageDeleted
{
    use Dispatchable, SerializesModels;

    /**
     * @var string
     */
    public $path;

    /**
     * CloudImageUploaded constructor.
     *
     * @param $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }
}