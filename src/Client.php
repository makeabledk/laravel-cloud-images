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
     * @param \Illuminate\Http\File | \Illuminate\Http\UploadedFile $image
     * @param null $filename
     * @return CloudImage
     * @throws \Exception
     */
    public function upload(File $image, $filename = null)
    {
        $filename = $filename ?: $image->hashName();

        if (! $this->disk()->putFileAs('', $image, $filename)) {
            throw new FailedUploadException('Failed to upload image');
        }

        if (($response = $this->guzzle->request('GET', $this->endpoint.'?image='.$filename))->getStatusCode() !== 200) {
            throw new FailedUploadException('Failed to parse file as image');
        }

        $url = str_replace('http://', 'https://', json_decode($response->getBody())->url);

        return new CloudImage($url, $filename);
    }

    /**
     * @return Filesystem
     */
    protected function disk()
    {
        return Storage::disk($this->diskName);
    }
}