<?php

namespace App\Services\Storage;

use App\Contracts\MediaUploadStorageContract;
use Illuminate\Support\Facades\Storage;

class S3MediaUploadService implements MediaUploadStorageContract
{
    public function generateUploadUrl(string $objectKey, string $contentType): string
    {

    /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        return Storage::disk('s3')->temporaryUploadUrl(
            $objectKey,
            now()->addMinutes(15),
            ['ContentType' => $contentType]
        )['url'];
    }

    public function objectExists(string $objectKey): bool
    {
        return Storage::disk('s3')->exists($objectKey);
    }

    public function publicUrl(string $objectKey): string
    {
        return Storage::disk('s3')->temporaryUrl($objectKey, now()->addHours(6));
    }
}