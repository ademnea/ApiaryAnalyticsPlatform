<?php

use App\Http\Controllers\Api\Iot\IotLocalMediaMockController;
use App\Http\Controllers\Api\Iot\IotMediaUploadUrlController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes (/api prefix, 60 req/min per user or IP)
|--------------------------------------------------------------------------
| Device readings, heartbeats and media confirmations do NOT come here:
| devices post to the gateway (apigateway.py / AWS API Gateway), which
| queues them for `php artisan iot:work`.
*/

// IoT devices request a presigned URL to upload media to S3 (or the local mock).
Route::post('iot/media/upload-url', [IotMediaUploadUrlController::class, 'requestUploadUrl']);

// Local S3 stand-in receiver, only used while IOT_MEDIA_DISK != s3.
Route::put('iot/media-mock/{key}', [IotLocalMediaMockController::class, 'receive'])
    ->name('iot.media.mock-upload')
    ->middleware('signed');

// Farmer mobile API (Section 4.8)
require __DIR__.'/farmer_api.php';
