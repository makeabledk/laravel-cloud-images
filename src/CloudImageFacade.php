<?php

namespace Makeable\CloudImages;

use Illuminate\Support\Facades\Facade;

class CloudImageFacade extends Facade
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
