<?php

namespace Makeable\CloudImages\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Makeable\CloudImages\Image;
use Makeable\CloudImages\Tests\Stubs\Product;
use Makeable\CloudImages\Tests\TestCase;

class CleanupTest extends TestCase
{
    use RefreshDatabase;

    /** @test * */
    public function it_deletes_unused_images()
    {
        $this->image();

        $this->assertEquals(1, Image::count());

        Artisan::call('cloud-images:cleanup');

        $this->assertEquals(0, Image::count());
    }

    /** @test * */
    public function it_preserves_used_images()
    {
        Product::create()->images()->save($this->image());

        $this->assertEquals(1, Image::count());

        Artisan::call('cloud-images:cleanup');

        $this->assertEquals(1, Image::count());
    }
}
