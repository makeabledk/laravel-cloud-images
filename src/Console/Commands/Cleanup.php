<?php

namespace Makeable\CloudImages\Console\Commands;

use Illuminate\Console\Command;
use Makeable\CloudImages\Image;

class Cleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cloud-images:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete images with no attachments';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $images = Image::doesntHave('attachments')->get()->each(function(Image $image) {
            $image->delete();
        });

        $this->info('Deleted '.$images->count().' images');
    }
}
