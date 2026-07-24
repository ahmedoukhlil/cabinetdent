<?php

return [
    /*
    |--------------------------------------------------------------------------
    | QR Code Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the QR code generation.
    |
    */

    'format' => env('QR_CODE_FORMAT', 'png'),
    'size' => env('QR_CODE_SIZE', 200),
    'margin' => env('QR_CODE_MARGIN', 0),
    'error_correction' => env('QR_CODE_ERROR_CORRECTION', 'M'), // L, M, Q, H
    'encoding' => env('QR_CODE_ENCODING', 'UTF-8'),
]; 