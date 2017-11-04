
# Laravel Cloud Images

[![Latest Version on Packagist](https://img.shields.io/packagist/v/makeabledk/laravel-cloud-images.svg?style=flat-square)](https://packagist.org/packages/makeabledk/laravel-cloud-images)
[![Build Status](https://img.shields.io/travis/makeabledk/laravel-cloud-images/master.svg?style=flat-square)](https://travis-ci.org/makeabledk/laravel-cloud-images)
[![StyleCI](https://styleci.io/repos/109057978/shield?branch=master)](https://styleci.io/repos/109057978)

This package provides a convenient to manage Google App Engine images through Laravel.

It assumes you already have a configured App Engine imageserver and GCS Bucket.

## Installation

You can install this package via composer:

``` bash
composer require makeabledk/laravel-cloud-images
```

On Laravel versions < 5.5, you must include the service provider and (optionally) register the facade in you `config/app.php`:

```php
'providers' => [
...
    \Makeable\CloudImages\CloudImagesServiceProvider::class,
]
``` 

```php
'aliases' => [
...
    'CloudImage' => \Makeable\CloudImages\CloudImageFacade::class,
]
```

This package depends on [https://github.com/Superbalist/laravel-google-cloud-storage](https://github.com/Superbalist/laravel-google-cloud-storage) - please follow the installation guide for adding the necessary config to `filesystems.php`.


## Basic usage

### Upload an image

Easily upload a `\Illuminate\Http\File` or `\Illuminate\Http\UploadedFile` to your GCS bucket and create a image-url for it.

```php
$file = request()->file('image'); // assuming you uploaded a file through a form
$uploaded = \CloudImage::upload($file); // filename will be a hash of the uploaded file
$uploadedToPath = \CloudImage::upload($file, 'path/filename.jpg'); // optionally specify path and filename yourself
        
echo $uploaded->url; // imageserver url, eg: http://lh3.googleusercontent.com/...
echo $uploaded->path; // path in bucket
```

### Delete an image

Using the `delete` method will both delete the bucket file and destroy serving-image URL.

```php
\CloudImage::delete('path/filename.jpg');
```

Note that image-serving URL's can take up to 24 hours to clear from cache

### Generating images on the fly

Now that our image is served by Google, we can manipulate it on the fly. Yay!

All you have to do to start manipulating images, is an instance of `ImageFactory`:

```php
$image = \CloudImage::upload($file)->make();
// or ...
$image = new \Makeable\CloudImages\ImageFactory($url); 
```

#### Contain to max dimension

```php
$image->maxDimension(800)->getUrl();
```

#### Crop to dimensions

```php
$image->crop(800, 500)->getUrl(); // Crop from top
$image->cropCenter(800, 500)->getUrl(); // Crop from center
```

#### Stretch to dimensions

```php
$image->scale(800, 500)->getUrl(); 
```

#### Custom parameters (advanced)

If the functionality you need is not provided by the package, you can specify your own

```php
$image->original()->param('fv')->getUrl(); // This image will be flipped vertically
```

While the official Google Documentation is quite poor, checkout this [Stackoverflow diskussion](https://stackoverflow.com/questions/25148567/list-of-all-the-app-engine-images-service-get-serving-url-uri-options) and try the possibilities out for yourself!

## Extended usage (recommended)

While uploading and serving images is fine, you will likely need to store the references in your database as well, and attach them to some existing models.

You need to both save the URL as well as the bucket-path (if you ever want to delete them).

This package provides an easy (and opinionated) way of doing that.

### Extended installation

Install `rutorika/sortable` package which is used to track sort-order 

```bash
composer require rutorika/sortable
```

Install `intervention/image` to read and store exif-data on your images

```bash
composer require intervention/image 
```

### Uploading images

```php
$image = \Makeable\CloudImages\Image::upload($file); // a persisted eloquent model 

echo $image->path; // bucket path
echo $image->url; // image-serving url
echo $image->meta; // exif data 
```

### Model attachment with multiple images

First, use the `HasImages` trait on your parent model.

```php
class Product extends Eloquent
{
    use Makeable\CloudImages\HasImages;
}
```

Now you have an `images` belongsToMany-relationship you can utilize.

```php
Product::first()->images()->attach(Image::first());
```

#### Ordering attached images

Attached images automatically adds in the order you attach them. However, you are free to reorder them afterwards.

```php
$product = Product::first();
$images = $product->images; // In this example we assume an collection of a few images

$product->images()->moveBefore($images->get(2), $images->first());
```

Checkout the *Sortable many to many* section of the [rutorika/sortable](https://github.com/boxfrommars/rutorika-sortable) package.

### Model attachment with a single image

If your model is expected to have just one image, you may use the convenient `image()` helper provided by the `HasImages` trait.

```php
$image = Product::first()->image(); // Always returns an Image instance - even if none uploaded
```

On the `Image` instance you may use the `make()` method to generate the size you need.

```php
$url =  $image->make()->cropCenter(500, 400)->getUrl(); // If no image attached, getUrl() will return NULL
```

#### Example usage

Here is some inspiration for a powerful model structure

```php
class Product extends Eloquent
{
    use \Makeable\CloudImages\HasImages;
    
    public function getImageSquareAttribute()
    {
        return $this->image()->make()->cropCenter(500, 400)->getUrl();
    }

    public function getImageWideAttribute()
    {
        return $this->image()->make()->cropCenter(1200, 400)->getUrl();
    }
}
```

Now you can access and append them as needed through your model

```php
echo Product::first()->image_square;
echo Product::first()->append('image_square', 'image_wide')->toArray(); // for array-casting
```

### Cleaning up old images

#### Deleting an image

When deleting an `Image` instance, the `CloudImage::delete()` method is automatically fired to delete the actual bucket file.

#### Deleting images with no attachment

Over time your `images` table may get bloated with images that no longer has model-attachments to them.

Use the `cloud-images:cleanup` command to delete images (along with the actual bucket files) that are no longer used.

```bash
php artisan cloud-images:cleanup
```


## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

You can run the tests with:

```bash
composer test
```

## Contributing

We are happy to receive pull requests for additional functionality. Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Rasmus Christoffer Nielsen](https://github.com/rasmuscnielsen)
- [All Contributors](../../contributors)

## License

Attribution-ShareAlike 4.0 International. Please see [License File](LICENSE.md) for more information.