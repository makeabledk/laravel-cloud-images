<?php

namespace Makeable\CloudImages\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Makeable\CloudImages\CloudImageFacade;
use Makeable\CloudImages\Events\CloudImageUploaded;
use Makeable\CloudImages\ImageFactory;
use Makeable\CloudImages\Exceptions\FailedUploadException;
use Makeable\CloudImages\Tests\TestCase;

class UploadTest extends TestCase
{
    /** @test **/
    public function it_uploads_images()
    {
        $uploaded = CloudImageFacade::upload(UploadedFile::fake()->image('original-filename.jpg'), 'test.jpg');

        $this->assertInstanceOf(CloudImageUploaded::class, $uploaded);
        $this->assertInstanceOf(ImageFactory::class, $uploaded->make());
        $this->assertEquals('https://localhost/somehash', $uploaded->url);
        $this->assertEquals('test.jpg', $uploaded->path);
    }

    /** @test **/
    public function it_hashes_filename_on_default()
    {
        $image = UploadedFile::fake()->image('original-filename.jpg');
        $hash = $image->hashName();

        $uploaded = CloudImageFacade::upload($image);
        $this->assertEquals($hash, $uploaded->path);
    }

    /** @test **/
    public function it_throws_exception_on_failed_upload()
    {
        $this->failHttpRequest();

        $this->expectException(FailedUploadException::class);

        CloudImageFacade::upload(UploadedFile::fake()->image('original-filename.jpg'), 'test.jpg');
    }

    /** @test **/
    public function it_dispatches_event_on_upload()
    {
        Event::fake();
        CloudImageFacade::upload(UploadedFile::fake()->image('original-filename.jpg'));
        Event::assertDispatched(CloudImageUploaded::class);
    }
}
