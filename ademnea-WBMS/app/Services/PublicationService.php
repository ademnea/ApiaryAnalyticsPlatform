<?php

namespace App\Services;

use App\Models\Publication;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class PublicationService
{
    /**
     * Create a new publication record with file uploads
     */
    public function createPublication(
        array $data,
        ?UploadedFile $pdf = null,
        ?UploadedFile $image = null
    ): Publication {
        // Validate files if provided
        if ($pdf) {
            if (!$this->validatePdf($pdf)) {
                throw new \InvalidArgumentException('Invalid PDF file');
            }
            $data['attachment_path'] = $this->uploadPdf($pdf);
            $data['attachment_filename'] = $pdf->getClientOriginalName();
        }

        if ($image) {
            if (!$this->validateImage($image)) {
                throw new \InvalidArgumentException('Invalid image file');
            }
            $data['image_path'] = $this->uploadImage($image);
            $data['image_filename'] = $image->getClientOriginalName();
        }

        // Generate slug
        $data['slug'] = Publication::generateSlug($data['author'], $data['title']);

        return Publication::create($data);
    }

    /**
     * Update an existing publication record with optional file replacements
     */
    public function updatePublication(
        Publication $publication,
        array $data,
        ?UploadedFile $pdf = null,
        ?UploadedFile $image = null
    ): Publication {
        // Handle PDF replacement
        if ($pdf) {
            if (!$this->validatePdf($pdf)) {
                throw new \InvalidArgumentException('Invalid PDF file');
            }
            // Delete old PDF if exists
            if ($publication->attachment_path && Storage::exists($publication->attachment_path)) {
                Storage::delete($publication->attachment_path);
            }
            $data['attachment_path'] = $this->uploadPdf($pdf);
            $data['attachment_filename'] = $pdf->getClientOriginalName();
        }

        // Handle image replacement
        if ($image) {
            if (!$this->validateImage($image)) {
                throw new \InvalidArgumentException('Invalid image file');
            }
            // Delete old image if exists
            if ($publication->image_path && Storage::exists($publication->image_path)) {
                Storage::delete($publication->image_path);
            }
            $data['image_path'] = $this->uploadImage($image);
            $data['image_filename'] = $image->getClientOriginalName();
        }

        $publication->update($data);
        return $publication;
    }

    /**
     * Permanently delete a publication and its associated files
     */
    public function deletePublication(Publication $publication): bool
    {
        // Delete files
        if ($publication->attachment_path && Storage::exists($publication->attachment_path)) {
            Storage::delete($publication->attachment_path);
        }
        if ($publication->image_path && Storage::exists($publication->image_path)) {
            Storage::delete($publication->image_path);
        }

        // Force delete the record
        return $publication->forceDelete();
    }

    /**
     * Publish a publication (set is_published=true and published_at=now)
     */
    public function publishPublication(Publication $publication): bool
    {
        return $publication->publish();
    }

    /**
     * Unpublish a publication (set is_published=false and published_at=null)
     */
    public function unpublishPublication(Publication $publication): bool
    {
        return $publication->unpublish();
    }

    /**
     * Search publications by keyword (author, title, publisher, year)
     */
    public function searchPublications(string $query, int $perPage = 20): LengthAwarePaginator
    {
        return Publication::where('author', 'LIKE', "%{$query}%")
            ->orWhere('title', 'LIKE', "%{$query}%")
            ->orWhere('publisher', 'LIKE', "%{$query}%")
            ->orWhere('publication_year', '=', (int)$query)
            ->forAdmin()
            ->latest()
            ->paginate($perPage);
    }

    /**
     * List all publications for admin (including soft deleted)
     */
    public function listPublicationsForAdmin(int $perPage = 20): LengthAwarePaginator
    {
        return Publication::forAdmin()
            ->latest()
            ->paginate($perPage);
    }

    /**
     * List published publications for public view
     */
    public function listPublishedPublications(int $perPage = 20, string $sortBy = 'published_at'): LengthAwarePaginator
    {
        return Publication::published()
            ->orderBy($sortBy, 'desc')
            ->orderBy('publication_year', 'desc')
            ->paginate($perPage);
    }

    /**
     * Filter publications by publication year
     */
    public function filterByYear(int $year, int $perPage = 20): LengthAwarePaginator
    {
        return Publication::published()
            ->where('publication_year', $year)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Upload and store a PDF file
     */
    public function uploadPdf(UploadedFile $file): string
    {
        $filename = $file->store('publications/pdfs', 'public');
        return $filename;
    }

    /**
     * Upload and store an image file
     */
    public function uploadImage(UploadedFile $file): string
    {
        $filename = $file->store('publications/images', 'public');
        return $filename;
    }

    /**
     * Delete a file from storage
     */
    public function deleteFile(string $path): bool
    {
        if (Storage::exists($path)) {
            return Storage::delete($path);
        }
        return true;
    }

    /**
     * Validate PDF file (format, size)
     * Max: 10 MB
     */
    public function validatePdf(UploadedFile $file): bool
    {
        $maxSize = 10 * 1024 * 1024; // 10 MB in bytes

        if ($file->getSize() > $maxSize) {
            return false;
        }

        if ($file->getMimeType() !== 'application/pdf') {
            return false;
        }

        return true;
    }

    /**
     * Validate image file (format, size)
     * Allowed: JPG, JPEG, PNG, WEBP, GIF
     * Max: 5 MB
     */
    public function validateImage(UploadedFile $file): bool
    {
        $maxSize = 5 * 1024 * 1024; // 5 MB in bytes
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

        if ($file->getSize() > $maxSize) {
            return false;
        }

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            return false;
        }

        return true;
    }

    /**
     * Get attachment file stream for download
     */
    public function getAttachmentStream(Publication $publication)
    {
        if (!$publication->hasAttachment()) {
            throw new \Exception('Attachment not found');
        }

        return Storage::download($publication->attachment_path, $publication->attachment_filename);
    }
}
