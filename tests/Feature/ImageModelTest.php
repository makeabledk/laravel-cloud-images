<?php

namespace Makeable\CloudImages\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Makeable\CloudImages\CloudImagesServiceProvider;
use Makeable\CloudImages\Image;
use Makeable\CloudImages\Tests\Stubs\Product;
use Makeable\CloudImages\Tests\TestCase;

class ImageModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test **/
    public function it_publishes_migrations()
    {
        $this->assertContains('database/migrations',
            array_first(ServiceProvider::pathsToPublish(CloudImagesServiceProvider::class))
        );
    }

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
    public function it_can_store_exif_data()
    {
        Config::set('cloud-images.read_exif', true);

        $image = Image::upload(new File(__DIR__.'/../image.jpg'), 'test.jpg');

        $this->assertEquals(1000, $image->meta['COMPUTED']['Height']);
    }

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
        $this->assertEquals(2, $product->images->get(1)->attachment->order);
    }

    /** @test **/
    public function it_finds_attachables_for_a_given_model()
    {
        list($image, $product1, $product2) = [$this->image(), Product::create(), Product::create()];

        $product1->images()->save($image);
        $product2->images()->save($image);

        $this->assertEquals(2, $image->attachables(Product::class)->count());
    }

    /** @test **/
    function it_deletes_cloud_image_on_model_deletion()
    {
        Storage::disk('gcs')->put('test.jpg', 'bar');

        $this->image()->delete();

        Storage::disk('gcs')->assertMissing('test.jpg');
    }

    /**
     * @return Image
     */
    private function image()
    {
        return Image::create([
            'path' => 'test.jpg',
            'url' => 'foo',
        ]);
    }
}
