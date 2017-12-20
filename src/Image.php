<?php

namespace Makeable\CloudImages;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Makeable\CloudImages\Jobs\DeleteCloudImage;

class Image extends Model
{
    /**
     * @var string
     */
    protected $table = 'images';

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
    protected $reservedForAttachable = null;

    /**
     * @var null
     */
    protected $reservedForTag = null;

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
     * @param string | null $path
     * @param string | null $visibility
     * @return Image
     */
    public static function upload($image, $path = null, $visibility = null)
    {
        $uploaded = resolve(Client::class)->upload($image, $path, $visibility);

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
            ->withPivot('tag', 'order');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attachments()
    {
        return $this->hasMany(ImageAttachment::class, 'image_id');
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
    public function replaceWith(self $image)
    {
        return tap($image, function ($image) {
            if ($this->exists) {
                ImageAttachment::where('image_id', $this->id)->update(['image_id' => $image->id]);
                $this->delete();
            } elseif ($this->reservedForAttachable) {
                $this->reservedForAttachable->images()->attach($image->id, ['tag' => $this->reservedForTag]);
            }
        });
    }

    /**
     * @param $attachable
     * @param null $tag
     * @return $this
     */
    public function reserveFor($attachable, $tag = null)
    {
        $this->reservedForAttachable = $attachable;
        $this->reservedForTag = $tag;

        return $this;
    }
}
