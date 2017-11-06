<?php

namespace Makeable\CloudImages\Tests\Feature;

use Makeable\CloudImages\ImageFactory;
use Makeable\CloudImages\Tests\TestCase;

class ImageFactoryTest extends TestCase
{
    /** @test **/
    public function it_defaults_to_original()
    {
        $this->assertEquals($this->url('s0'), $this->cloudImage()->get());
    }

    /** @test **/
    public function it_can_scale_to_max_dimension()
    {
        $this->assertEquals($this->url('s400'), $this->cloudImage()->maxDimension(400)->get());
    }

    /** @test **/
    public function it_can_scale_to_dimensions()
    {
        $this->assertEquals($this->url('s-w800-h600'), $this->cloudImage()->scale(800, 600)->get());
    }

    /** @test **/
    public function it_can_crop_to_dimensions()
    {
        $this->assertEquals($this->url('c-w800-h600'), $this->cloudImage()->crop(800, 600)->get());
    }

    /** @test **/
    public function it_can_crop_from_center()
    {
        $this->assertEquals($this->url('n-w800-h600'), $this->cloudImage()->cropCenter(800, 600)->get());
    }

    /** @test **/
    public function it_accepts_custom_parameters()
    {
        $this->assertEquals($this->url('s0-fv'), $this->cloudImage()->param('fv')->get());
    }

    /** @test **/
    public function it_returns_null_when_url_is_null()
    {
        $this->assertNull((new ImageFactory(null))->param('xyz')->get());
    }

    /**
     * @return ImageFactory
     */
    protected function cloudImage()
    {
        return new ImageFactory($this->url());
    }

    /**
     * @param string $params
     * @return string
     */
    protected function url($params = null)
    {
        return 'https://localhost/somehash'.($params ? '='.$params : '');
    }
}
