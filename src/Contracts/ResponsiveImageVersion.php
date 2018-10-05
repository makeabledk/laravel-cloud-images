<?php

namespace Makeable\CloudImages\Contracts;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

interface ResponsiveImageVersion extends Arrayable, JsonSerializable
{
    /**
     * @return string
     */
    public function get();

    /**
     * @return int
     */
    public function getWidth();
}
