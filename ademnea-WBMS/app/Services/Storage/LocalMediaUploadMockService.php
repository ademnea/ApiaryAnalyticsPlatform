<?php

namespace App\Services\Storage;

use App\Contracts\MediaUploadStorageContract;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class LocalMediaUploadMockService implements MediaUploadStorageContract
{
    private const DISK = 'local_iot_mock';

    /**
     * S3's temporaryUploadUrl() has no local-disk equivalent — Laravel's
     * local driver cannot generate a real presigned PUT URL. This returns
     * a signed URL to a Laravel route (see routes/api.php) that accepts a
     * raw file body and writes it to the mock disk, so the device-side
     * upload flow can be tested end-to-end without AWS.
     */
    public function generateUploadUrl(string $objectKey, string $contentType): string
    {
        return URL::temporarySignedRoute(
            'iot.media.mock-upload',
            now()->addMinutes(15),
            ['key' => base64_encode($objectKey)]
        );
    }

    public function objectExists(string $objectKey): bool
    {
        return Storage::disk(self::DISK)->exists($objectKey);
    }

    public function publicUrl(string $objectKey): string
    {
        return Storage::disk(self::DISK)->url($objectKey);
    }
}