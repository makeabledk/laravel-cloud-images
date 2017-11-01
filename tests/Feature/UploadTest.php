<?php

namespace Makeable\CloudImages\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Makeable\CloudImages\CloudImage;
use Makeable\CloudImages\FailedUploadException;
use Makeable\CloudImages\Tests\TestCase;

class UploadTest extends TestCase
{
    /** @test **/
    public function it_uploads_images()
    {
        $image = CloudImage::upload(UploadedFile::fake()->image('original-filename.jpg'), 'test.jpg');

        $this->assertInstanceOf(CloudImage::class, $image);
        $this->assertEquals('https://localhost/somehash', $image->url);
        $this->assertEquals('test.jpg', $image->filename);
    }

    /** @test **/
    public function it_hashes_filename_on_default()
    {
        $image = UploadedFile::fake()->image('original-filename.jpg');
        $hash = $image->hashName();

        $image = CloudImage::upload($image);
        $this->assertEquals($hash, $image->filename);
    }

    /** @test **/
    public function it_throws_exception_on_failed_upload()
    {
        $this->failHttpRequest();

        $this->expectException(FailedUploadException::class);

        CloudImage::upload(UploadedFile::fake()->image('original-filename.jpg'), 'test.jpg');
    }
}
