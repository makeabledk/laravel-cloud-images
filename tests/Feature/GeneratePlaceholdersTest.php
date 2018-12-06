<?php

namespace Makeable\CloudImages\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Makeable\CloudImages\Image;
use Makeable\CloudImages\Tests\TestCase;

class GeneratePlaceholdersTest extends TestCase
{
    use RefreshDatabase;

    /** @test * */
    public function it_generates_placeholders_and_sets_dimensions()
    {
        $placeholderGenerator = $this->usePlaceholders()->testActualFactory();

        $image = Image::create([
            'path' => '',
            'url' => $placeholderGenerator->testImageUrl(),
        ]);

        Artisan::call('cloud-images:placeholders');

        // It generates placeholder
        $this->assertNotNull($placeholder = $image->refresh()->tiny_placeholder);
        $this->assertContains('base64', $placeholder);

        // It sets dimensions when NULL (for upgrading versions)
        $this->assertNotNull($image->width);
        $this->assertGreaterThan(1000, $image->width);
        $this->assertGreaterThan(1000, $image->height);
        $this->assertGreaterThan(1 * 1024 * 1024, $image->size);
    }
}
