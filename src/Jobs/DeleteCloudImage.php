<?php

namespace Makeable\CloudImages\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Makeable\CloudImages\Client;

class DeleteCloudImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string
     */
    protected $path;

    /**
     * Create a new job instance.
     *
     * @param  string  $path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Execute the job.
     *
     * @param  Client  $client
     */
    public function handle(Client $client)
    {
        $client->delete($this->path);
    }
}
