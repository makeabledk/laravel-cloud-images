<?php

namespace Makeable\CloudImages\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Makeable\CloudImages\Tests\TestCase;

class ResponsiveTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function it_generates_responsive_versions()
    {
        $this->assertTrue(true);
//        $start = microtime(true);
//        (getimagesize('https://lh3.googleusercontent.com/LgiUrgL8JvZlSq2IBk6RCVwuure0xy3L_HoGMm-IeDub_1UfSLmaMeTcPF2UxYda48rmvzyGPMpG8skP-sJLcL5X=s0'));
//        dd(microtime(true) - $start);
    }
}
