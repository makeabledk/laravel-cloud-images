<?php

namespace Makeable\CloudImages\Tests\Feature;

use BadMethodCallException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\File;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Makeable\CloudImages\Image;
use Makeable\CloudImages\ImageFactory;
use Makeable\CloudImages\Tests\TestCase;
use Makeable\CloudImages\TinyPlaceholder;

class ResponsiveTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function it_needs_an_image_instance()
    {
        $this->expectException(BadMethodCallException::class);

        $factory = new ImageFactory($this->image()->url);
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
        $this->assertEquals('n-w500-h300-fv', Str::after($full->get(), '='));

        $smallest = $versions->last();
        $this->assertEquals([143, 85], $smallest->getDimensions());
        $this->assertEquals('n-w143-h85-fv', Str::after($smallest->get(), '='));
    }

    /** @test **/
    public function the_smallest_version_is_a_tiny_placeholder_when_used()
    {
        $this->usePlaceholders();
        $image = Image::upload(new File(__DIR__.'/../image.jpg'), 'test.jpg');

        $versions = $image->make()->responsive()->get();

        $this->assertInstanceOf(TinyPlaceholder::class, $versions->last());
    }

    /** @test **/
    public function the_placeholder_stretches_to_the_dimensions_of_the_generated_image()
    {
        $this->usePlaceholders();
        $image = Image::upload(new File(__DIR__.'/../image.jpg'), 'test.jpg');

        $versions = $image->make()->responsive()->crop(600, 300)->get();

        $this->assertEquals([600, 300], $versions->last()->getDimensions());
    }

    /** @test **/
    public function responsive_factory_casts_to_an_array_suitable_for_api_response()
    {
        $this->usePlaceholders();
        $image = Image::upload(new File(__DIR__.'/../image.jpg'), 'test.jpg');

        $response = $image->make()->responsive()->crop(600, 300)->toArray();

        $this->assertArrayHasKey('src', $response);
        $this->assertArrayHasKey('srcset', $response);
        $this->assertArrayHasKey('width', $response);
    }

    /** @test **/
    public function responsive_factory_casts_to_json_suitable_for_api_response()
    {
        $this->usePlaceholders();
        $image = Image::upload(new File(__DIR__.'/../image.jpg'), 'test.jpg');

        $factory = $image->make()->responsive()->crop(600, 300);

        // Assert that produces srcset is fairly big
        $this->assertGreaterThan(2500, strlen(json_encode($factory)));
    }

    /** @test **/
    public function regression__it_always_returns_integers()
    {
        $versions = $this->image(null, 1234, 234)->make()->responsive()->maxDimension(123)->get();
        $this->assertEquals(123, $versions->first()->getWidth());
        $this->assertEquals(23, $versions->first()->getHeight());

        $versions = $this->image(null, 1234, 234)->make()->responsive()->maxDimension(123.12)->get();
        $this->assertEquals(123, $versions->first()->getWidth());
        $this->assertEquals(23, $versions->first()->getHeight());
    }

    /** @test **/
    public function regression__it_also_scales_original_sizes_responsively()
    {
        // On responsive images we never get =s0 - rather we get the explicit scaling
        $versions = $this->image(null, 1000, 800)->make()->original()->responsive()->get();
        $this->assertStringEndsWith('=s-w1000-h800', $versions->get(0)->get());
        $this->assertStringEndsWith('=s-w836-h668', $versions->get(1)->get());

        // Same thing
        $versions = $this->image(null, 1000, 800)->make()->responsive()->original()->get();
        $this->assertStringEndsWith('=s-w1000-h800', $versions->get(0)->get());
        $this->assertStringEndsWith('=s-w836-h668', $versions->get(1)->get());

        // Same thing
        $versions = $this->image(null, 1000, 800)->make()->responsive()->get();
        $this->assertStringEndsWith('=s-w1000-h800', $versions->get(0)->get());
        $this->assertStringEndsWith('=s-w836-h668', $versions->get(1)->get());
    }

    /** @test **/
    public function regression__it_handles_none_existent_images()
    {
        $image = new Image();
        $responsive = $image->make()->responsive()->scale(1000, 1000);

        $this->assertEquals([], $responsive->get()->all());
        $this->assertNull($responsive->getSrc());
        $this->assertEquals('', $responsive->getSrcset());
        $this->assertStringContainsString('src=""', $responsive->getHtml());
        $this->assertEquals([
            'src' => null,
            'srcset' => '',
            'width' => null,
        ], $responsive->toArray());
    }
}
