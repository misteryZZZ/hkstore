<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image Driver
    |--------------------------------------------------------------------------
    |
    | Intervention Image supports "GD Library" and "Imagick" to process images
    | internally. You may choose one of them according to your PHP
    | configuration. By default PHP's "GD Library" implementation is used.
    |
    | Supported: "gd", "imagick"
    |
    */

    'driver' => 'gd',
    'crop' => [
        '-' => null,
        'graphic' => null,
        'ebook' => null,
        'audio' => null,
        'video' => null,
        'void'  => null
    ],
    'watermark' => [
        '-' => false,
        'graphic' => true,
        'ebook' => false,
        'audio' => false,
        'video' => false,
        'void'  => null
    ]

];
