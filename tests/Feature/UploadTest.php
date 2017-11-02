<?php

namespace Makeable\CloudImages\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Makeable\CloudImages\ImageFactory;
use Makeable\CloudImages\FailedUploadException;
use Makeable\CloudImages\Tests\TestCase;

class UploadTest extends TestCase
{
    /** @test **/
    public function it_uploads_images()
    {
        $image = ImageFactory::upload(UploadedFile::fake()->image('original-filename.jpg'), 'test.jpg');

        $this->assertInstanceOf(ImageFactory::class, $image);
        $this->assertEquals('https://localhost/somehash', $image->url);
        $this->assertEquals('test.jpg', $image->filename);
    }

    /** @test **/
    public function it_hashes_filename_on_default()
    {
        $image = UploadedFile::fake()->image('original-filename.jpg');
        $hash = $image->hashName();

        $image = ImageFactory::upload($image);
        $this->assertEquals($hash, $image->filename);
    }

    /** @test **/
    public function it_throws_exception_on_failed_upload()
    {
        $this->failHttpRequest();

        $this->expectException(FailedUploadException::class);

        ImageFactory::upload(UploadedFile::fake()->image('original-filename.jpg'), 'test.jpg');
    }
}
