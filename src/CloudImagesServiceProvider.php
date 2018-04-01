<?php

namespace Makeable\CloudImages;

use Illuminate\Support\ServiceProvider;
use Intervention\Image\ImageServiceProvider;
use Makeable\CloudImages\Console\Commands\Cleanup;
use Rutorika\Sortable\SortableServiceProvider;
use Superbalist\LaravelGoogleCloudStorage\GoogleCloudStorageServiceProvider;

class CloudImagesServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (! class_exists('CreateImagesTable')) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_images_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_images_table.php'),
                __DIR__.'/../database/migrations/create_image_attachments_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time() + 1).'_create_image_attachments_table.php'),
            ], 'migrations');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([Cleanup::class]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cloud-images.php', 'cloud-images');

        $this->app->register(GoogleCloudStorageServiceProvider::class);
        $this->app->singleton(Client::class, function () {
            return new Client('gcs', 'https://'.config('filesystems.disks.gcs.bucket'), new \GuzzleHttp\Client);
        });

        if (class_exists(ImageServiceProvider::class)) {
            $this->app->register(ImageServiceProvider::class);

            if (config('cloud-images.read_exif') === null) {
                config('cloud-images.read_exif', true);
            }
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
}
