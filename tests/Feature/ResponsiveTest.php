<?php

namespace Makeable\CloudImages\Tests\Feature;

use BadMethodCallException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Makeable\CloudImages\ImageFactory;
use Makeable\CloudImages\Tests\TestCase;

class ResponsiveTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function it_needs_an_image_instance()
    {
        $this->expectException(BadMethodCallException::class);

        $factory = new ImageFactory($this->image());
        $factory->responsive()->get();
    }

    /** @test **/
    public function it_returns_a_collection_of_image_factories()
    {
        $versions = $this->image()->make()->responsive()->get();

        $this->assertCount(13, $versions);
        $this->assertInstanceOf(Collection::class, $versions);
        $this->assertInstanceOf(ImageFactory::class, $versions->first());
    }

    /** @test **/
    public function the_number_of_versions_depends_on_the_transformed_dimensions()
    {
        // Original image is 1000x1000 but we are only requesting 500x300 hence the fewer versions
        $versions = $this->image()->make()->responsive()->scale(500, 300)->get();

        $this->assertCount(8, $versions);
    }

    /** @test **/
    public function it_applies_transformations_across_versions()
    {
        // It shouldn't matter if transformation happens before or after responsive() call
        $versions = $this->image()->make()->param('fv')->responsive()->cropCenter(500, 300)->get();

        $full = $versions->first();
        $this->assertEquals([500, 300], $full->getDimensions());
        $this->assertEquals('n-w500-h300-fv', str_after($full->get(), '='));

        $smallest = $versions->last();
        $this->assertEquals([143, 85], $smallest->getDimensions());
        $this->assertEquals('n-w143-h85-fv', str_after($smallest->get(), '='));
    }
}
