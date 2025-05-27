<?php

namespace Makeable\CloudImages\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Makeable\CloudImages\Image;

class GeneratePlaceholders extends Command
{
    protected $signature = 'cloud-images:placeholders
                            {--limit : Limit the number of images to process}
                            {--offset= : Offset the starting point for processing}
                            {--desc : Sort images in descending order by ID}';

    protected $description = 'Generate missing placeholders, dimensions and sizes for images';

    public function handle(): void
    {
        if (! config('cloud-images.use_tiny_placeholders')) {
            $this->error('Placeholders are disabled in your config');
            return;
        }

        $limit = $this->option('limit');
        $offset = (int) $this->option('offset') ?: 0;
        $chunkSize = 50;
        $processed = 0;

        $this->info("Starting to process images"
            . ($offset ? " from offset {$offset}" : '')
            . ($limit  ? " (up to {$limit} total)" : '')
            . " in chunks of {$chunkSize}.");

        while (true) {
            // How many we still need this round
            $take = $limit > 0
                ? min($chunkSize, $limit - $processed)
                : $chunkSize;

            // If we've hit the limit, break out
            if ($take <= 0) {
                break;
            }

            // Build the base query for this chunk
            $query = Image::query()
                ->where(function ($q) {
                    $q->whereNull('width')
                        ->orWhereNull('height')
                        ->orWhereNull('size')
                        ->orWhereNull('tiny_placeholder')
                        ->orWhere('tiny_placeholder', '');
                })
                ->when($this->option('desc'), fn($q) => $q->orderBy('id', 'desc'))
                ->skip($offset + $processed)
                ->take($take);

            $images = $query->get();

            if ($images->isEmpty()) {
                break;
            }

            $this->comment(sprintf(
                'Processing chunk: images %d–%d (got %d)…',
                $offset + $processed + 1,
                $offset + $processed + $images->count(),
                $images->count()
            ));

            $images->each(function (Image $image) {
                $this->comment("  • #{$image->id}");
                $this->maybeUpgrade($image);

                if (empty($image->tiny_placeholder)) {
                    $image->tiny_placeholder = $image->placeholder()->create();
                }

                $image->save();
            });

            $processed += $images->count();
        }

        $this->info("Done: processed {$processed} image(s).");
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
            $dim = getimagesize($original);
            $image->width  = Arr::get($dim, 0);
            $image->height = Arr::get($dim, 1);

            $this->comment("  › Upgraded #{$image->id} — fetched dimensions");
        }
    }
}
