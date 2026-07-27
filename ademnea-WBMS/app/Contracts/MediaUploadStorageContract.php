<?php

namespace App\Contracts;

interface MediaUploadStorageContract
{
    /**
     * Generates a time-limited URL the device can PUT its media file to
     * directly. Real S3 implementation returns a presigned S3 URL. The
     * local mock implementation returns a URL pointing at a Laravel route
     * that accepts a raw PUT and writes to the local disk, simulating S3.
     */
    public function generateUploadUrl(string $objectKey, string $contentType): string;

    /**
     * Confirms the object actually exists at the given key — a defensive
     * check before the queue worker creates a database record pointing
     * at it, since a device could submit a confirmation for an upload
     * that silently failed.
     */
    public function objectExists(string $objectKey): bool;

    /**
     * Public-facing URL for serving the media file, used when the API
     * returns media records to the Farmer API / admin dashboard.
     */
    public function publicUrl(string $objectKey): string;
}