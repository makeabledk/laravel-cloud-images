<?php

return [

    /*
     * The default folder to upload images to
     *
     * If none specified, it will be uploaded to bucket root
     */
    'default_upload_path' => null,

    /*
     * Enable reading exif from images.
     *
     * Requires Intervention/Image package
     */
    'read_exif' => null,

    /**
     * Automatically generate a tiny placeholder for uploaded images.
     * These will be used for making responsive images.
     */
    'use_tiny_placeholders' => true
];
