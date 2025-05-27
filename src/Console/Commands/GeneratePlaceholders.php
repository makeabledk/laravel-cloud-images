<?php

namespace Makeable\CloudImages\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Makeable\CloudImages\Image;

class GeneratePlaceholders extends Command
{
    protected $signature = 'cloud-images:placeholders
                            {--limit=1000 : Limit the number of images to process}
                            {--offset= : Offset the starting point for processing}
                            {--desc : Sort images in descending order by ID}';

    protected $description = 'Generate missing placeholders, dimensions and sizes for images';

    public function handle(): void
    {
        if (! config('cloud-images.use_tiny_placeholders')) {
            $this->error('Placeholders are disabled in your config');

            return;
        }

        // Build query with options - only query images with missing data
        $results = Image::query()
            ->where(function ($query) {
                $query->whereNull('width')
                    ->orWhereNull('height')
                    ->orWhereNull('size')
                    ->orWhereNull('tiny_placeholder')
                    ->orWhere('tiny_placeholder', '');
            })
            ->when($this->option('desc'), fn ($query) => $query->orderBy('id', 'desc'))
            ->when($this->option('offset'), fn ($query, $offset) => $query->skip($offset))
            ->when($this->option('limit'), fn ($query, $limit) => $query->take($limit))
            ->get();

        $this->comment("Found {$results->count()} images with missing data to process...");

        $results->each(function (Image $image) {
            $this->comment("Processing image #{$image->id}");
            $this->maybeUpgrade($image);

            if (empty($image->tiny_placeholder)) {
                $image->tiny_placeholder = $image->placeholder()->create();
            }

            $image->save();
        });

        $this->info('Done');
    }

    /**
     * If package was upgraded from <= 0.16 it could be missing width, height & size.
     *
     * @param  Image  $image
     */
    protected function maybeUpgrade(Image $image): void
    {
        $original = $image->make()->original()->get();

        if ($image->size === null) {
            $headers = array_change_key_case(get_headers($original, true));
            $image->size = $headers['content-length'];
        }

        if ($image->width === null) {
            $image->width = Arr::get($dim = getimagesize($original), 0);
            $image->height = Arr::get($dim, 1);

            $this->comment("Upgraded image {$image->id} - fetched dimensions");
        }
    }
}
