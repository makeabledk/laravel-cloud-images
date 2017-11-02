<?php

namespace Makeable\CloudImages;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\File;

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
     * @param \Illuminate\Http\File|\Illuminate\Http\UploadedFile|File $image
     * @param null $path
     * @return object
     * @throws FailedUploadException
     */
    public function upload(File $image, $path = null)
    {
        $path = $path ?: $image->hashName();
        $namespace = dirname($path) ?: '';
        $filename = basename($path);

        if (! $this->disk()->putFileAs($namespace, $image, $filename)) {
            throw new FailedUploadException('Failed to upload image to bucket');
        }

        if (($response = $this->guzzle->request('GET', $this->endpoint.'?image='.$path))->getStatusCode() !== 200) {
            throw new FailedUploadException('Failed to parse file as image');
        }

        $url = str_replace('http://', 'https://', json_decode($response->getBody())->url);

        return (object) compact('url', 'path');
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

        return true;
    }

    /**
     * @return Filesystem
     */
    protected function disk()
    {
        return Storage::disk($this->diskName);
    }
}
