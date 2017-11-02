<?php

use Illuminate\Support\Facades\Facade;

class CloudImage extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \Makeable\CloudImages\Client::class;
    }
}