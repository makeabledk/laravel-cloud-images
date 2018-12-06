<?php

namespace Makeable\CloudImages\Tests\Fakes;

use Makeable\CloudImages\ImageFactory;
use Makeable\CloudImages\TinyPlaceholder;

class FakeTinyPlaceholder extends TinyPlaceholder
{
    protected static $testViaHttp = false;

    public function testImageUrl()
    {
        return 'https://lh3.googleusercontent.com/nVlGxZ1Gjz_FP_xjbqTFDZtT4mM6LpqNUlqf-FR5yOpuzfYckoFdpS66HBKVJkUCycFqP7pFJkFUKnE88cGj5ZlGrg';
    }

    public function testActualFactory($bool = true)
    {
        static::$testViaHttp = $bool;

        return $this;
    }

    public function factory()
    {
        $factory = new class($this->testImageUrl()) extends ImageFactory {
            public static $shouldMock;

            public function getContents()
            {
                return static::$shouldMock
                    ? file_get_contents(__DIR__.'/../placeholder.jpg')
                    : parent::getContents();
            }
        };

        $factory::$shouldMock = ! static::$testViaHttp;

        return $factory->blur()->maxDimension(32);
    }
}
