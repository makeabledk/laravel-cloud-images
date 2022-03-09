<?php

namespace Makeable\CloudImages;

use Illuminate\Support\ServiceProvider;
use Intervention\Image\ImageServiceProvider;
use Makeable\CloudImages\Console\Commands\Cleanup;
use Makeable\CloudImages\Console\Commands\GeneratePlaceholders;
use Makeable\CloudImages\Contracts\DimensionCalculator;
use Spatie\GoogleCloudStorage\GoogleCloudStorageServiceProvider;

class CloudImagesServiceProvider extends ServiceProvider
{
    /**
     * @var int
     */
    protected $publishedMigrationsIndex = 0;

    public function boot()
    {
        if (! class_exists('CreateImagesTable')) {
            $this->publishMigration('create_images_table.php.stub');
            $this->publishMigration('create_image_attachments_table.php.stub');
        }
        if (! class_exists('AddResponsiveFieldsToImagesTable')) {
            $this->publishMigration('add_responsive_fields_to_images_table.php.stub');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                Cleanup::class,
                GeneratePlaceholders::class,
            ]);
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'cloud-images');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cloud-images.php', 'cloud-images');

        $this->app->bind(DimensionCalculator::class, FileSizeOptimizedDimensionCalculator::class);

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

    /**
     * Publish a given migration stub.
     *
     * @param $file
     */
    protected function publishMigration($file)
    {
        $this->publishes([
            __DIR__.'/../database/migrations/'.$file => database_path('migrations/'.date('Y_m_d_His', time() + $this->publishedMigrationsIndex).'_'.str_replace('.php.stub', '.php', $file)),
        ], 'migrations');

        $this->publishedMigrationsIndex++; // ensures files are in right order
    }
}
