<?php

namespace Makeable\CloudImages\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Makeable\CloudImages\Image;
use Makeable\CloudImages\Tests\TestCase;

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
}
