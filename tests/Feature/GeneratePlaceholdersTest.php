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
        $this->assertStringContainsString('base64', $placeholder);

        // It sets dimensions when NULL (for upgrading versions)
        $this->assertNotNull($image->width);
        $this->assertGreaterThan(1000, $image->width);
        $this->assertGreaterThan(1000, $image->height);
        $this->assertGreaterThan(1 * 1024 * 1024, $image->size);
    }

    /** @test * */
    public function it_only_updates_images_with_missing_data()
    {
        $placeholderGenerator = $this->usePlaceholders()->testActualFactory();

        // Create an image with missing data
        $imageWithMissingData = Image::create([
            'path' => 'missing-data',
            'url' => $placeholderGenerator->testImageUrl(),
        ]);

        // Create an image with complete data
        $completeImage = Image::create([
            'path' => 'complete',
            'url' => $placeholderGenerator->testImageUrl(),
            'width' => 1200,
            'height' => 800,
            'size' => 2 * 1024 * 1024,
            'tiny_placeholder' => 'data:image/jpeg;base64,testbase64data'
        ]);

        // Run the command
        Artisan::call('cloud-images:placeholders');

        // Refresh the images
        $imageWithMissingData->refresh();
        $completeImage->refresh();

        // The image with missing data should be updated
        $this->assertNotNull($imageWithMissingData->tiny_placeholder);
        $this->assertNotNull($imageWithMissingData->width);
        $this->assertNotNull($imageWithMissingData->height);
        $this->assertNotNull($imageWithMissingData->size);

        // The complete image should remain unchanged
        $this->assertEquals('data:image/jpeg;base64,testbase64data', $completeImage->tiny_placeholder);
        $this->assertEquals(1200, $completeImage->width);
        $this->assertEquals(800, $completeImage->height);
        $this->assertEquals(2 * 1024 * 1024, $completeImage->size);
    }

    /** @test * */
    public function it_respects_limit_and_offset_options()
    {
        $placeholderGenerator = $this->usePlaceholders()->testActualFactory();

        // Create 5 images
        for ($i = 0; $i < 5; $i++) {
            Image::create([
                'path' => "image-{$i}",
                'url' => $placeholderGenerator->testImageUrl(),
            ]);
        }

        // Run the command with limit=2 and offset=1
        Artisan::call('cloud-images:placeholders', [
            '--limit' => 2,
            '--offset' => 1
        ]);

        // Get all images
        $images = Image::orderBy('id')->get();

        // First image should not be updated (due to offset)
        $this->assertNull($images[0]->tiny_placeholder);

        // Second and third images should be updated (limit=2 starting from offset=1)
        $this->assertNotNull($images[1]->tiny_placeholder);
        $this->assertNotNull($images[2]->tiny_placeholder);

        // Fourth and fifth images should not be updated (beyond limit)
        $this->assertNull($images[3]->tiny_placeholder);
        $this->assertNull($images[4]->tiny_placeholder);
    }

    /** @test * */
    public function it_respects_desc_option()
    {
        $placeholderGenerator = $this->usePlaceholders()->testActualFactory();

        // Create 3 images
        for ($i = 0; $i < 3; $i++) {
            Image::create([
                'path' => "image-{$i}",
                'url' => $placeholderGenerator->testImageUrl(),
            ]);
        }

        // Run the command with desc and limit=1
        Artisan::call('cloud-images:placeholders', [
            '--desc' => true,
            '--limit' => 1
        ]);

        // Get all images
        $images = Image::orderBy('id')->get();

        // Only the last image should be updated (due to desc order and limit=1)
        $this->assertNull($images[0]->tiny_placeholder);
        $this->assertNull($images[1]->tiny_placeholder);
        $this->assertNotNull($images[2]->tiny_placeholder);
    }
}
