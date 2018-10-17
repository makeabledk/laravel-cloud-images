<?php

namespace Makeable\CloudImages\Console\Commands;

use Illuminate\Console\Command;
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
                $this->comment('Preparing '.$images->count().' placeholders...');
            })
            ->chunk(50)
            ->each(function (Collection $images) {
                $this->comment('Generating '.$images->count().' placeholders...');
                $images->each(function (Image $image) {
                    $image->tiny_placeholder = $image->placeholder()->create();
                    $image->save();
                });
            });

        $this->info('Finished generating placeholders');
    }
}
