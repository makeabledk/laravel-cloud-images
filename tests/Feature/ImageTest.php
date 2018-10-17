<?php

namespace Makeable\CloudImages\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Makeable\CloudImages\Image;
use Makeable\CloudImages\Tests\TestCase;
use Makeable\CloudImages\TinyPlaceholder;

class ImageTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function it_inserts_uploaded_images_to_database()
    {
        $image = Image::upload(UploadedFile::fake()->image('test.jpg'), 'test.jpg');

        $this->assertInstanceOf(Image::class, $image);
        $this->assertEquals(1, $image->id);
        $this->assertEquals('test.jpg', $image->path);
        $this->assertEquals('https://localhost/somehash', $image->url);
        $this->assertEquals(10, $image->width);
        $this->assertEquals(10, $image->height);
//        $this->assertGreaterThan(50, $image->size); // Image size can vary a bit between environments
//        $this->assertLessThan(100, $image->size);
    }

    /** @test **/
    public function it_deletes_cloud_image_on_model_deletion()
    {
        $image = $this->image();

        Storage::disk('gcs')->assertExists('test.jpg');

        $image->delete();

        Storage::disk('gcs')->assertMissing('test.jpg');
    }

    /** @test **/
    public function it_can_store_exif_data()
    {
        Config::set('cloud-images.read_exif', true);

        $image = Image::upload(new File(__DIR__.'/../image.jpg'), 'test.jpg');

        $this->assertEquals(1000, $image->meta['COMPUTED']['Height']);
    }

    /** @test **/
    public function it_generates_a_tiny_placeholder()
    {
        $this->usePlaceholders()->testActualFactory();

        $image = Image::upload(new File(__DIR__.'/../image.jpg'), 'test.jpg');

        $info = getimagesize($image->tiny_placeholder);

        $this->assertEquals(32, $info[0]);
        $this->assertEquals('image/jpeg', $info['mime']);
    }

    /** @test **/
    public function it_casts_to_a_responsive_html_img_tag()
    {
        $image = Image::upload(new File(__DIR__.'/../image.jpg'), 'test.jpg');

        $this->assertContains('<img', $html = (string) $image);
        $this->assertContains('srcset', $html = (string) $image);
        $this->assertNotContains('sizes', $html = (string) $image);
    }

    /** @test **/
    public function it_casts_to_a_responsive_html_img_tag_with_placeholder_when_enabled()
    {
        $this->usePlaceholders();

        $image = Image::upload(new File(__DIR__.'/../image.jpg'), 'test.jpg');

        $this->assertContains('<img', $html = (string) $image);
        $this->assertContains('srcset', $html = (string) $image);
        $this->assertContains('sizes', $html = (string) $image);
        $this->assertContains('onload', $html = (string) $image);
    }
}
