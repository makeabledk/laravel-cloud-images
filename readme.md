
# Laravel Cloud Images

[![Latest Version on Packagist](https://img.shields.io/packagist/v/makeabledk/laravel-cloud-images.svg?style=flat-square)](https://packagist.org/packages/makeabledk/laravel-cloud-images)
[![Build Status](https://img.shields.io/travis/makeabledk/laravel-cloud-images/master.svg?style=flat-square)](https://travis-ci.org/makeabledk/laravel-cloud-images)
[![StyleCI](https://styleci.io/repos/109057978/shield?branch=master)](https://styleci.io/repos/109057978)

This package provides a convenient to manage Google App Engine images through Laravel.

It assumes you already have a configured App Engine imageserver and GCS Bucket.

## Install

You can install this package via composer:

``` bash
composer require makeabledk/laravel-cloud-images
```

On Laravel versions < 5.5, you must include the service provider in you `config/app.php`:

```php
'providers' => [
...
    /*
     * Package Service Providers...
     */
     
    \Makeable\CloudImages\CloudImagesServiceProvider::class,
]
```

This package depends on [https://github.com/Superbalist/laravel-google-cloud-storage]() - please follow the installation guide for adding the necessary config to `filesystems.php`.


## Example usages

### Upload an image

Say that you are posting an `image` file to a Laravel controller. 

You can easily fetch that and upload to your GCS bucket and create a image-url for it.

```php
<?php 

class ImageController extends Controller
{
    public function store(Request $request) 
    {
        $image = CloudImage::upload($request->file('image'));
        
        echo $image->url; // imageserver url, eg: http://lh3.googleusercontent.com/...
        echo $image->filename; // filename in bucket, either the one you specified or a hash of the uploaded file
    }
}
```

### Manipulating images on the fly

Now that our image is served by Google, we can manipulate it on the fly


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