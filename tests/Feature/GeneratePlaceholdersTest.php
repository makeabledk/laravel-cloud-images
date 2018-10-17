<?php

namespace Makeable\CloudImages\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Makeable\CloudImages\Tests\TestCase;

class GeneratePlaceholdersTest extends TestCase
{
    use RefreshDatabase;

    /** @test * */
    public function it_deletes_unused_images()
    {
        $this->usePlaceholders();
        $this->assertNull(($image = $this->image()->refresh())->tiny_placeholder);

        Artisan::call('cloud-images:placeholders');

        $this->assertNotNull($placeholder = $image->refresh()->tiny_placeholder);
        $this->assertContains('base64', $placeholder);
    }
}
