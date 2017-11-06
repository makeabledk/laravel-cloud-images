<?php

namespace Makeable\CloudImages;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Makeable\CloudImages\Jobs\DeleteCloudImage;

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
     * @var null
     */
    protected $reservedFor = null;

    /**
     * Delete from bucket and image-service on deletion.
     */
    public static function boot()
    {
        parent::boot();
        static::deleting(function (Image $image) {
            dispatch(new DeleteCloudImage($image->path));
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
            ->withPivot('order');
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

    /**
     * @param Image $image
     * @return Image
     */
    public function replaceWith(Image $image)
    {
        return tap($image, function ($image) {
            if($this->exists) {
                ImageAttachment::where('image_id', $this->id)->update(['image_id' => $image->id]);
                $this->delete();
            }
            elseif ($this->reservedFor) {
                $this->reservedFor->images()->save($image);
            }
        });
    }

    /**
     * @param $model
     * @return $this
     */
    public function reservedFor($model)
    {
        $this->reservedFor = $model;

        return $this;
    }
}
