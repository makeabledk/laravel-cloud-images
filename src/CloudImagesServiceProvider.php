<?php

namespace Makeable\CloudImages;

use Illuminate\Support\ServiceProvider;
use Makeable\CloudImages\Console\Commands\Cleanup;

class CloudImagesServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (config('cloud-images.model') !== null) {
            $this->registerImageDependencies();
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cloud-images.php', 'cloud-images');

        $this->app->register(\Superbalist\LaravelGoogleCloudStorage\GoogleCloudStorageServiceProvider::class);
        $this->app->singleton(Client::class, function ($app) {
            return new Client('gcs', 'https://'.config('filesystems.disks.gcs.bucket'), new \GuzzleHttp\Client);
        });

        if (class_exists(\Intervention\Image\ImageServiceProvider::class)) {
            $this->app->register(\Intervention\Image\ImageServiceProvider::class);
        }
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

    protected function registerImageDependencies()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([Cleanup::class]);
        }
    }
}
