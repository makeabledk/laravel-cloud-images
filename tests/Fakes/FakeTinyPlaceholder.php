<?php

namespace Makeable\CloudImages\Tests\Fakes;

use Makeable\CloudImages\Image;
use Makeable\CloudImages\ImageFactory;
use Makeable\CloudImages\TinyPlaceholder;

class FakeTinyPlaceholder extends TinyPlaceholder
{
    public function factory()
    {
        return (new ImageFactory('https://lh3.googleusercontent.com/nVlGxZ1Gjz_FP_xjbqTFDZtT4mM6LpqNUlqf-FR5yOpuzfYckoFdpS66HBKVJkUCycFqP7pFJkFUKnE88cGj5ZlGrg'))
            ->blur()
            ->maxDimension(32);
    }
}