<?php

namespace Makeable\CloudImages\Tests\Feature;

use Makeable\CloudImages\CloudImage;
use Makeable\CloudImages\Tests\TestCase;

class ImageTest extends TestCase
{
    /** @test **/
    public function it_defaults_to_original()
    {
        $this->assertEquals($this->url('s0'), $this->image()->getUrl());
    }

    /** @test **/
    public function it_can_scale_to_max_dimension()
    {
        $this->assertEquals($this->url('s400'), $this->image()->maxDimension(400)->getUrl());
    }

    /** @test **/
    public function it_can_scale_to_dimensions()
    {
        $this->assertEquals($this->url('s-w800-h600'), $this->image()->scale(800, 600)->getUrl());
    }

    /** @test **/
    public function it_can_crop_to_dimensions()
    {
        $this->assertEquals($this->url('c-w800-h600'), $this->image()->crop(800, 600)->getUrl());
    }

    /** @test **/
    public function it_can_crop_from_center()
    {
        $this->assertEquals($this->url('n-w800-h600'), $this->image()->cropCenter(800, 600)->getUrl());
    }

    /** @test **/
    public function it_accepts_custom_parameters()
    {
        $this->assertEquals($this->url('s0-fv'), $this->image()->param('fv')->getUrl());
    }

    /**
     * @return CloudImage
     */
    protected function image()
    {
        return new CloudImage($this->url(), 'image.jpg');
    }

    /**
     * @param string $params
     * @return string
     */
    protected function url($params = '')
    {
        return 'https://localhost/somehash'.$params;
    }
}
