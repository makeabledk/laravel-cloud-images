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
        'width' => 'int',
        'height' => 'int',
        'size' => 'int',
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
     * @param File|UploadedFile $file
     * @param string | null $path
     * @param string | null $visibility
     * @return Image
     */
    public static function upload($file, $path = null, $visibility = null)
    {
        $uploaded = resolve(Client::class)->upload($file, $path, $visibility);

        $image = new static([
            'path' => $uploaded->path,
            'url' => $uploaded->url,
            'size' => $file->getSize(),
            'width' => array_get($dim = getimagesize($file), 0),
            'height' => array_get($dim, 1),
        ]);

        if (config('cloud-images.read_exif')) {
            $image->meta = app('image')->make($file->getRealPath())->exif();
        }

        if (config('cloud-images.use_tiny_placeholders')) {
            $image->tiny_placeholder = $image->placeholder()->create();
        }

        return tap($image)->save();
    }

    /**
     * @param $class
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function attachables($class)
    {
        return $this->morphedByMany($class, 'attachable', 'image_attachments', 'image_id')
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
     * @return array
     */
    public function getDimensions()
    {
        return [$this->width, $this->height];
    }

    /**
     * @return ImageFactory
     */
    public function make()
    {
        return ImageFactory::make($this);
    }

    /**
     * @return TinyPlaceholder
     */
    public function placeholder()
    {
        return app()->makeWith(TinyPlaceholder::class, ['image' => $this]);
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

    /**
     * @return float|int
     */
    public function getAspectRatioAttribute()
    {
        return $this->width / $this->height;
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
     * @throws \Throwable
     */
    public function __toString()
    {
        return $this->make()->responsive()->getHtml();
    }
}
