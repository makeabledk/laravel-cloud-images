<?php

namespace Makeable\CloudImages\Tests;

use Illuminate\Support\Facades\Storage;
use Makeable\CloudImages\Client;
use Makeable\CloudImages\CloudImageFacade;
use Makeable\CloudImages\CloudImagesServiceProvider;
use Makeable\CloudImages\Image;
use Makeable\CloudImages\Tests\Fakes\FakeGuzzleClient;
use Makeable\CloudImages\Tests\Fakes\FakeTinyPlaceholder;
use Makeable\CloudImages\TinyPlaceholder;

class TestCase extends \Illuminate\Foundation\Testing\TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->fakeServices();
    }

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        putenv('APP_ENV=testing');
        putenv('APP_DEBUG=true');
        putenv('CACHE_DRIVER=array');
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');

        $app = require __DIR__.'/../vendor/laravel/laravel/bootstrap/app.php';

        $app->useEnvironmentPath(__DIR__.'/..');
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $app->register(CloudImagesServiceProvider::class);
        $app->afterResolving('migrator', function ($migrator) {
            $migrator->path(__DIR__.'/migrations/');
        });

        // Register facade
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('CloudImageFacade', CloudImageFacade::class);

        return $app;
    }

    public function fakeServices()
    {
        Storage::fake('gcs');

        app()->singleton(FakeGuzzleClient::class, function () {
            return new FakeGuzzleClient();
        });

        app()->bind(TinyPlaceholder::class, FakeTinyPlaceholder::class);

        app()->singleton(Client::class, function ($app) {
            return new Client('gcs', 'localhost', $app->make(FakeGuzzleClient::class));
        });

        config()->set('cloud-images.use_tiny_placeholders', false);
    }

    /**
     * @param int $status
     */
    public function failHttpRequest($status = 400)
    {
        app()->singleton(FakeGuzzleClient::class, function () use ($status) {
            return new FakeGuzzleClient($status);
        });
    }

    /**
     * @param string $path
     * @param int $width
     * @param int $height
     * @param int $size
     * @return Image
     */
    protected function image($path = null, $width = 1000, $height = 1000, $size = null)
    {
        Storage::disk('gcs')->put($path = $path ?: 'test.jpg', 'foo');

        $url = 'foo';
        $size = $size ?: $this->mb(1);

        return Image::create(compact('path', 'url', 'width', 'height', 'size'));
    }

    /**
     * @param $mb
     * @return int
     */
    protected function mb($mb)
    {
        return $mb * 1024 * 1024;
    }

    /**
     * @param $x
     * @param $y
     * @return float|int
     */
    protected function area($x, $y)
    {
        return $x * $y;
    }
}
