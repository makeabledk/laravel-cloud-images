
# Laravel Cloud Images

[![Latest Version on Packagist](https://img.shields.io/packagist/v/makeabledk/laravel-cloud-images.svg?style=flat-square)](https://packagist.org/packages/makeabledk/laravel-cloud-images)
[![Build Status](https://img.shields.io/travis/makeabledk/laravel-cloud-images/master.svg?style=flat-square)](https://travis-ci.org/makeabledk/laravel-cloud-images)
[![StyleCI](https://styleci.io/repos/109057978/shield?branch=master)](https://styleci.io/repos/109057978)

This package provides a convenient to manage Google App Engine Images API through Laravel.

Images API allows you to upload an image once to your GCS bucket and afterwards generate unlimited thumbnails just by requesting the specified size in the image-url. No delay, storage concerns or re-generate commands.

**Important**

Please checkout [https://github.com/makeabledk/appengine-php-imageserver](https://github.com/makeabledk/appengine-php-imageserver) for more information on Images API and how to setup an imageserver for your project.

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

### Upgrading from 0.16.x -> 0.17.0 

This release introduces new database fields for responsive images. Please publish migrations and optionally generate placeholders.

```php
php artisan vendor:publish --provider="Makeable\CloudImages\CloudImagesServiceProvider"
php artisan migrate
php artisan cloud-images:placeholders
```

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
$image->maxDimension(150)->get();
```
Example result:

![Example image 1](https://lh3.googleusercontent.com/nVlGxZ1Gjz_FP_xjbqTFDZtT4mM6LpqNUlqf-FR5yOpuzfYckoFdpS66HBKVJkUCycFqP7pFJkFUKnE88cGj5ZlGrg=s150)

#### Crop to dimensions

```php
$image->crop(300, 100)->get(); // Crop from top
$image->cropCenter(300, 100)->get(); // Crop from center
```

Example result:

![Example image 1](https://lh3.googleusercontent.com/nVlGxZ1Gjz_FP_xjbqTFDZtT4mM6LpqNUlqf-FR5yOpuzfYckoFdpS66HBKVJkUCycFqP7pFJkFUKnE88cGj5ZlGrg=c-w300-h100)

![Example image 2](https://lh3.googleusercontent.com/nVlGxZ1Gjz_FP_xjbqTFDZtT4mM6LpqNUlqf-FR5yOpuzfYckoFdpS66HBKVJkUCycFqP7pFJkFUKnE88cGj5ZlGrg=n-w300-h100)

#### Stretch to dimensions

```php
$image->scale(300, 100)->get(); 
```
Example result:

![Example image 1](https://lh3.googleusercontent.com/nVlGxZ1Gjz_FP_xjbqTFDZtT4mM6LpqNUlqf-FR5yOpuzfYckoFdpS66HBKVJkUCycFqP7pFJkFUKnE88cGj5ZlGrg=s-w300-h100)

#### Blur

```php
$image->blur(15)->get(); // Blur 15% 
```
Example result:

![Example image 1](https://lh3.googleusercontent.com/nVlGxZ1Gjz_FP_xjbqTFDZtT4mM6LpqNUlqf-FR5yOpuzfYckoFdpS66HBKVJkUCycFqP7pFJkFUKnE88cGj5ZlGrg=c-w300-h100-fSoften=1,15,0)

#### Custom parameters (advanced)

If the functionality you need is not provided by the package, you can specify your own google-compatible parameters:

```php
$image->original()->param('fv')->get(); // This image will be flipped vertically
```
![Example image 3](https://lh3.googleusercontent.com/nVlGxZ1Gjz_FP_xjbqTFDZtT4mM6LpqNUlqf-FR5yOpuzfYckoFdpS66HBKVJkUCycFqP7pFJkFUKnE88cGj5ZlGrg=n-w300-h100-fv)

Checkout our [makeabledk/appengine-php-imageserver](https://github.com/makeabledk/appengine-php-imageserver) repository for more information on available parameters.

## Media Library usage (recommended)

For the examples so far there has been no need to publish any migrations. You are completely free to only use this package for uploading and retrieving image files from Google. 

However, while uploading and serving images is all well and good, you will likely need to store the references in your database and attach them to some existing models.

This package will likely provide you with most of the functionality you'll ever need for dealing with images in your application.

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
$images = $product->images; // In this example we assume a collection of a few images

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
// Always returns an Image instance - even if none uploaded
$image = Product::first()->image(); 
```

On the `Image` instance you may use the `make()` method to generate the size you need.

```php
// Returns a url (string) or NULL if no image attached
$url =  $image->make()->cropCenter(500, 400)->get(); 
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
In Laravel 5.5, ApiResources would be a great place to append your image sizes as well.

### Responsive images

Given the previous example of a product image, we may use the `responsive()` method to generate a collection of responsive image sizes.

By doing this, we can serve `srcset` optimized images on our website.

```php
// Returns collection of ImageFactory instances width contiously smaller images
Product::first()->image()->make()->original()->responsive()->get(); 

// Returns contents of the html srcset attribute
Product::first()->image()->make()->original()->responsive()->getSrcSet(); 

// Returns an array containing src, srcet and width attribute - especially useful for API responses
Product::first()->image()->make()->original()->responsive()->toArray(); 

// Returns <img> element with srcset attribute
Product::first()->image()->make()->original()->responsive()->toHtml(); 
(string) Product::first()->image()->make()->original()->responsive(); 
```

Of course all the available transformations are still available for responsive images.

```php
Product::first()->image()->make()->original()->responsive()->toHtml(); 
```

The approach for responsive images is heavily inspired by the [spatie/laravel-medialibrary](https://github.com/spatie/laravel-medialibrary) package and offer the same functionality (including placeholders).

Consider [reading their documentation](https://docs.spatie.be/laravel-medialibrary/v7/responsive-images/getting-started-with-responsive-images) for a very thorough explanation of the concept.

Also be sure to checkout their demo here: [Responsive images demo](https://docs.spatie.be/laravel-medialibrary/demo/responsive-images)

Finally consider checking out [ResponsiveTest.php](https://github.com/makeabledk/laravel-cloud-images/blob/master/tests/Feature/ResponsiveTest.php) for more usage examples this package offers.

### Cleaning up old images

#### Deleting an image

When deleting an `Image` instance, the `CloudImage::delete()` method is automatically fired to delete the actual bucket file.

#### Deleting images with no attachment

Over time your `images` table may get bloated with images that no longer has model-attachments to them.

Use the `cloud-images:cleanup` command to delete images (along with the actual bucket files) that are no longer used.

```bash
php artisan cloud-images:cleanup
```

## Testing

You can run the tests with:

```bash
composer test
```

## Contributing

We are happy to receive pull requests for additional functionality. Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Rasmus Christoffer Nielsen](https://github.com/rasmuscnielsen)
- Spatie for their awesome [spatie/laravel-medialibrary](https://github.com/spatie/laravel-medialibrary) 
- [All Contributors](../../contributors)

## License

Attribution-ShareAlike 4.0 International. Please see [License File](LICENSE.md) for more information.