<?php

namespace Makeable\CloudImages;

use Config;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Makeable\CloudImages\Events\CloudImageDeleted;
use Makeable\CloudImages\Events\CloudImageUploaded;
use Makeable\CloudImages\Exceptions\FailedDeletionException;
use Makeable\CloudImages\Exceptions\FailedUploadException;

class Client
{
    /**
     * @var string
     */
    protected $diskName;

    /**
     * @var string
     */
    protected $endpoint;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $guzzle;

    /**
     * Client constructor.
     *
     * @param $diskName string
     * @param $endpoint string
     * @param $guzzle
     */
    public function __construct($diskName, $endpoint, $guzzle)
    {
        $this->diskName = $diskName;
        $this->endpoint = $endpoint;
        $this->guzzle = $guzzle;
    }

    /**
     * @param $path
     * @return bool
     * @throws FailedDeletionException
     */
    public function delete($path)
    {
        if (($response = $this->guzzle->request('DELETE', $this->endpoint.'?image='.$path))->getStatusCode() !== 200) {
            throw new FailedDeletionException('Failed to stop serving image');
        }

        if (! $this->disk()->delete($path)) {
            throw new FailedDeletionException('Failed to delete image from bucket');
        }

        return tap(new CloudImageDeleted($path), function ($deleted) {
            event($deleted);
        });
    }

    /**
     * @param \Illuminate\Http\File|\Illuminate\Http\UploadedFile $image
     * @param null $path
     * @param string $visibility
     * @return object
     * @throws FailedUploadException
     */
    public function upload($image, $path = null, $visibility = null)
    {
        $path = $path ?: $image->hashName(Config::get('cloud-images.default_upload_path'));
        $visibility = $visibility ?: 'private';
        $namespace = dirname($path) ?: '';
        $filename = basename($path);

        if (! $this->disk()->putFileAs($namespace, $image, $filename, ['visibility' => 'private'])) {
            throw new FailedUploadException('Failed to upload image to bucket');
        }

        if (($response = $this->guzzle->request('GET', $this->endpoint.'?image='.$path))->getStatusCode() !== 200) {
            throw new FailedUploadException('Failed to parse file as image');
        }

        if ($visibility !== 'private') {
            $this->disk()->setVisibility($path, $visibility);
        }

        return tap(new CloudImageUploaded($path, json_decode($response->getBody())->url), function ($uploaded) {
            event($uploaded);
        });
    }

    /**
     * @return Filesystem
     */
    protected function disk()
    {
        return Storage::disk($this->diskName);
    }
}
