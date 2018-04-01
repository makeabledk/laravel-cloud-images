
# Laravel Cloud Images

[![Latest Version on Packagist](https://img.shields.io/packagist/v/makeabledk/laravel-cloud-images.svg?style=flat-square)](https://packagist.org/packages/makeabledk/laravel-cloud-images)
[![Build Status](https://img.shields.io/travis/makeabledk/laravel-cloud-images/master.svg?style=flat-square)](https://travis-ci.org/makeabledk/laravel-cloud-images)
[![StyleCI](https://styleci.io/repos/109057978/shield?branch=master)](https://styleci.io/repos/109057978)

This package provides a convenient to manage Google App Engine images through Laravel.

Please checkout [https://github.com/makeabledk/appengine-php-imageserver](https://github.com/makeabledk/appengine-php-imageserver) for more information on how to setup an imageserver for your project.

This package assumes you already have a configured App Engine imageserver and GCS Bucket.

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

Add a new `gcs` disk to your `filesystems.php` config

```php
'gcs' => [
    'driver' => 'gcs',
    'project_id' => env('GOOGLE_CLOUD_PROJECT_ID', 'your-project-id'),
    'key_file' => env('GOOGLE_CLOUD_KEY_FILE', '/path/to/service-account.json'), 
    'bucket' => env('GOOGLE_CLOUD_STORAGE_BUCKET', 'your-bucket'),
],
```

See [https://github.com/Superbalist/laravel-google-cloud-storage](https://github.com/Superbalist/laravel-google-cloud-storage) for more details about configuring `filesystems.php`.

## Basic usage

### Upload an image

Easily upload a `\Illuminate\Http\File` or `\Illuminate\Http\UploadedFile` to your GCS bucket and create an image-url for it.

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

### The good stuff: Generating images on the fly

Now that our image is served by Google, we can manipulate it on the fly. 

All you have to do to start manipulating images, is an instance of `ImageFactory`:

```php
$image = \CloudImage::upload($file)->make();
// or ...
$image = new \Makeable\CloudImages\ImageFactory($url); 
```

#### Contain to max dimension

```php
$image->maxDimension(800)->get();
```

#### Crop to dimensions

```php
$image->crop(800, 500)->get(); // Crop from top
$image->cropCenter(800, 500)->get(); // Crop from center
```

#### Stretch to dimensions

```php
$image->scale(800, 500)->get(); 
```

#### Custom parameters (advanced)

If the functionality you need is not provided by the package, you can specify your own google-compatible parameters:

```php
$image->original()->param('fv')->get(); // This image will be flipped vertically
```

While the [official Google Documentation](https://cloud.google.com/appengine/docs/standard/java/images/#using_if_lang_is_java_getservingurl_endif_if_lang_is_python_get_serving_url_endif) is poor to say the least, checkout this [Stackoverflow diskussion](https://stackoverflow.com/questions/25148567/list-of-all-the-app-engine-images-service-get-serving-url-uri-options) and try out the possibilities for yourself!

## Extended usage (recommended)

While uploading and serving images is fine, you will likely need to store the references in your database and attach them to some existing models.

You will need to save both the URL as well as the bucket-path in case you ever want to delete them.

This package provides an easy and opinionated way of doing that.

### Extended installation

#### 1. Install `rutorika/sortable` package which is used to track sort-order (required)

```bash
composer require rutorika/sortable
```

#### 2. Install `intervention/image` to read and store exif-data on your images (optional)

```bash
composer require intervention/image 
```

#### 3. Publish and run migrations 
```bash
php artisan vendor:publish --provider="Makeable\CloudImages\CloudImagesServiceProvider"
php artisan migrate
```

### Uploading images

```php
$image = \Makeable\CloudImages\Image::upload($file); // returns a persisted Image model instance (eloquent)

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

Now you have an `images()` belongs-to-many relationship you can utilize as you normally would:

```php
Product::first()->images()->attach(Image::first());
```

#### Ordering attached images

Images will be kept in the order you attach them. However, you are free to reorder them afterwards.

```php
$product = Product::first();
$images = $product->images; // In this example we assume an collection of a few images

$product->images()->moveBefore($images->get(2), $images->first());
```

Checkout the *Sortable many to many* section of the [rutorika/sortable](https://github.com/boxfrommars/rutorika-sortable) package.

### Model attachment with a single image

If your model is expected to have just one image, you may use the convenient `image()` helper provided by the same `HasImages` trait.

```php
class Product extends Eloquent
{
    use Makeable\CloudImages\HasImages;
}
```

```php
$image = Product::first()->image(); // Always returns an Image instance - even if none uploaded
```

On the `Image` instance you may use the `make()` method to generate the size you need.

```php
$url =  $image->make()->cropCenter(500, 400)->get(); // returns NULL if no image attached
```

#### Differentiating between image types

Sometimes you may wish to have different types of 'single images' on a model. Use the optional `tag` parameter to achieve this behavior.

```php
Product::first()->image('featured');
Product::first()->image('cover');
```
Note: Tagging is only intended through the `image($tag)` helper as the `rutorika/sortable` package does not differentiate between tags when applying `order`. 

#### Replacing images

Use the `replaceWith` method on the `Image` model to swap any `Image` with another while preserving attachments.

This is especially useful in combination with the `image()` helper:

```php
Product::first()->image('featured')->replaceWith(Image::upload($file));
```
- If the product did not have a 'featured' image, it would simple attach the new one
- If the product did already have 'featured' image it would get replaced, and the old one deleted

##### Pro tip: Use an eloquent mutator to set the new image 

```php
class Product extends Eloquent
{
    use \Makeable\CloudImages\HasImages;
    
    public function setImageAttribute($file)
    {
        return $this->image()->replaceWith(Image::upload($file));
    }
}
```

```php
Product::first()->image = request('image'); // replace image with an UploadedFile 'image'
```

In your controller it would work seamlessly when validating and `filling` the model (Laravel 5.5 example).
```php
public function store(Request $request)
{
    return Product::create(
        $request->validate([
            'image' => 'required|image',
            // ... some other fields
        ])
    );
}
```

### Configuring image sizes per model

Often times you want a few pre-configured sizes available. In this example we would like 'square' and 'wide' available on our `Product` model.

We may extend the `Image` model and use that on our `Product->images()` relationship.

```php
class Product extends Eloquent
{
    use \Makeable\CloudImages\HasImages;
    
    protected $useImageClass = ProductImage::class;
}
```

```php
class ProductImage extends \Makeable\CloudImages\Image
{
    public function getSquareAttribute()
    {
        return $this->make()->cropCenter(500, 500)->get();
    }

    public function getWideAttribute()
    {
        return $this->make()->cropCenter(1200, 400)->get();
    }
}
```

Now you can access the sizes simply by referencing them as properties.
```php
echo Product::first()->image()->square; // single image usage
echo Product::first()->images->first()->wide; // multiple images usage
```

Remember to add the sizes to `$appends` attribute if you want them available when casting to array:

```php
class ProductImage extends Image
{
    protected $appends = ['square', 'wide'];
    
    // ...
}
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