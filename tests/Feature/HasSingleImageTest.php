<?php

namespace Makeable\CloudImages\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Makeable\CloudImages\Image;
use Makeable\CloudImages\Tests\Stubs\Product;
use Makeable\CloudImages\Tests\TestCase;

class HasSingleImageTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function it_has_a_getter_for_as_single_image()
    {
        $product = Product::create();
        $product->images()->save($this->image());

        $this->assertEquals('foo', $product->image()->url);
    }

    /** @test **/
    public function it_defaults_to_empty_image()
    {
        $this->assertNull(Product::create()->image()->url);
    }

    /** @test **/
    public function it_replaces_with_another_image()
    {
        $product = Product::create();
        $product->images()->save($image1 = $this->image());

        $image1->replaceWith($image2 = $this->image());

        $this->assertTrue($product->image()->is($image2));
        $this->assertNull(Image::find($image1->id));
    }

    /** @test **/
    public function it_replaces_with_image_even_if_currently_has_none()
    {
        $product = Product::create();
        $product->image()->replaceWith($image = $this->image());

        $this->assertTrue($product->fresh()->image()->is($image));
    }

    /** @test **/
    public function it_can_fetch_an_image_of_a_given_tag()
    {
        $product = Product::create();
        $product->images()->attach(($feature = $this->image())->id, ['tag' => 'feature']);
        $product->images()->attach(($cover = $this->image())->id, ['tag' => 'cover']);
        $product->fresh();

        $this->assertTrue($product->image()->is($feature));
        $this->assertTrue($product->image('feature')->is($feature));
        $this->assertTrue($product->image('cover')->is($cover));
        $this->assertFalse($product->image('foo')->exists);
    }
}
