<?php

namespace Makeable\CloudImages\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Makeable\CloudImages\Image;

class GeneratePlaceholders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cloud-images:placeholders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate all placeholders';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (! config('cloud-images.use_tiny_placeholders')) {
            $this->error('Placeholders are disabled in your config');

            return;
        }

        Image::all()
            ->tap(function (Collection $images) {
                $this->comment('Preparing '.$images->count().' images...');
            })
            ->chunk(50)
            ->each(function (Collection $images) {
                $this->comment('Generating '.$images->count().' placeholders...');
                $images->each(function (Image $image) {
                    $this->maybeUpgrade($image);

                    $image->tiny_placeholder = $image->placeholder()->create();
                    $image->save();
                });
            });

        $this->info('Finished generating placeholders');
    }

    /**
     * If package was upgraded from <= 0.16 it could be missing width, height & size.
     *
     * @param  Image  $image
     */
    protected function maybeUpgrade(Image $image)
    {
        if ($image->width === null) {
            $original = $image->make()->original()->get();
            $headers = array_change_key_case(get_headers($original, true));

            $image->size = $headers['content-length'];
            $image->width = Arr::get($dim = getimagesize($original), 0);
            $image->height = Arr::get($dim, 1);

            $this->comment("Upgraded image {$image->id} - fetched dimensions");
        }
    }
}
