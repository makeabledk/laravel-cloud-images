<?php

namespace Makeable\CloudImages\Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Makeable\CloudImages\CloudImageFacade;
use Makeable\CloudImages\Events\CloudImageDeleted;
use Makeable\CloudImages\Events\CloudImageUploaded;
use Makeable\CloudImages\Exceptions\FailedDeletionException;
use Makeable\CloudImages\ImageFactory;
use Makeable\CloudImages\Exceptions\FailedUploadException;
use Makeable\CloudImages\Tests\Fakes\FakeGuzzleClient;
use Makeable\CloudImages\Tests\TestCase;

class DeleteTest extends TestCase
{
    /** @test **/
    public function it_deletes_images()
    {
        $client = \Mockery::mock(new FakeGuzzleClient);
        $this->app->instance(FakeGuzzleClient::class, $client);
        $this->putFile('test.jpg');

        $deleted = CloudImageFacade::delete('test.jpg');

        $client->shouldHaveReceived('request', ['DELETE', 'localhost?image=test.jpg']);
        $this->assertInstanceOf(CloudImageDeleted::class, $deleted);
        $this->assertEquals('test.jpg', $deleted->path);
    }

    /** @test **/
    public function it_throws_exception_on_failed_bucket_deletion()
    {
        $this->expectException(FailedDeletionException::class);

        // test.jpg does not exist in storage

        CloudImageFacade::delete('test.jpg');
    }

    /** @test **/
    public function it_throws_exception_on_failed_http_request()
    {
        $this->putFile('test.jpg');
        $this->failHttpRequest();
        $this->expectException(FailedDeletionException::class);

        CloudImageFacade::delete('test.jpg');
    }

    /** @test **/
    public function it_dispatches_event_on_deletion()
    {
        $this->putFile('test.jpg');
        Event::fake();

        CloudImageFacade::delete('test.jpg');

        Event::assertDispatched(CloudImageDeleted::class);
    }

    /**
     * @param $name
     */
    private function putFile($name)
    {
        Storage::disk('gcs')->put($name, 'foo');
    }
}
