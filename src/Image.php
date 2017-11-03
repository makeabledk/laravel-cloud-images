<?php

namespace Makeable\CloudImages;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;

class Image extends Model
{
    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * @var array
     */
    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * Delete from bucket and image-service on deletion.
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
     * @return Image
     */
    public static function upload($image, $path = null)
    {
        $uploaded = resolve(Client::class)->upload($image, $path);

        return static::create([
            'path' => $uploaded->path,
            'url' => $uploaded->url,
            'meta' => config('cloud-images.read_exif')
                ? app('image')->make($image->getRealPath())->exif()
                : null,
        ]);
    }

    /**
     * @param $class
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function attachables($class)
    {
        return $this->morphedByMany($class, 'attachable', 'image_attachments')
            ->withPivot('order')
            ->as('attachment');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attachments()
    {
        return $this->hasMany(ImageAttachment::class);
    }

    /**
     * @return ImageFactory
     */
    public function make()
    {
        return new ImageFactory($this->url);
    }
}
