<?php

namespace Makeable\CloudImages;

use Illuminate\Support\ServiceProvider;

class CloudImagesServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations')
        ], 'migrations');
    }

    public function register()
    {
        $this->app->register(\Superbalist\LaravelGoogleCloudStorage\GoogleCloudStorageServiceProvider::class);
        $this->app->singleton(Client::class, function ($app) {
            return new Client('gcs', 'https://'.config('filesystems.disks.gcs.bucket'), new \GuzzleHttp\Client);
        });
    }

    /**
     * @return array
     */
    public function provides()
    {
        return [
            Client::class,
        ];
    }
}
