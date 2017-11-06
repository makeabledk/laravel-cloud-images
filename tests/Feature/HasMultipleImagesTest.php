<?php

namespace Makeable\CloudImages\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Makeable\CloudImages\Tests\Stubs\Product;
use Makeable\CloudImages\Tests\TestCase;

class HasMultipleImagesTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function it_attaches_to_other_models()
    {
        $image = $this->image();
        $product = Product::create();

        $product->images()->attach($image);

        $this->assertTrue($product->images->first()->is($image));
    }

    /** @test **/
    public function it_sorts_attachments_by_order()
    {
        list($product, $image1, $image2) = [Product::create(), $this->image(), $this->image()];

        $product->images()->sync([$image2->id, $image1->id]);

        $this->assertTrue($product->images->first()->is($image2));
        $this->assertEquals(2, $product->images->get(1)->pivot->order);
    }

    /** @test **/
    public function it_can_rearrange_the_order()
    {
        $product = Product::create();
        $product->images()->sync([$this->image()->id, $this->image()->id]);

        $product->images()->moveBefore($product->images->get(1), $product->images->first());

        $this->assertEquals(2, $product->fresh()->images->first()->id);
    }

    /** @test **/
    public function it_finds_attachables_for_a_given_model()
    {
        list($image, $product1, $product2) = [$this->image(), Product::create(), Product::create()];

        $product1->images()->save($image);
        $product2->images()->save($image);

        $this->assertEquals(2, $image->attachables(Product::class)->count());
    }
}
