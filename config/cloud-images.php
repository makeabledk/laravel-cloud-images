<?php

return [

    /**
     * The model which should be used for HasImages trait
     */
    'model' => \Makeable\CloudImages\Image::class,

    /**
     * Enable reading exif from images.
     *
     * Requires Intervention/Image package
     */
    'read_exif' => false

];