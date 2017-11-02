<?php

namespace Makeable\CloudImages;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;

class Image extends Model
{
    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * @var array
     */
    protected $casts = [
        'meta' => 'array'
    ];

    /**
     * Delete from bucket and image-service on deletion
     */
    public static function boot()
    {
        static::deleted(function (Image $image) {
            resolve(Client::class)->delete($image->path);
        });
    }

    /**
     * @param File|UploadedFile $image
     * @param null $path
     * @return ImageFactory
     */
    public static function upload($image, $path = null)
    {
        $uploaded = resolve(Client::class)->upload($image, $path);

        return static::create([
            'path' => $uploaded->path,
            'url' => $uploaded->url,
            'meta' => \Intervention\Image\Image::make($image->getRealPath())->exif()
        ]);
    }

    /**
     * @return ImageFactory
     */
    public function make()
    {
        return new ImageFactory($this->url);
    }
}