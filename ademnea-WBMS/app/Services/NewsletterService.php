<?php

namespace App\Services;

use App\Models\Newsletter;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class NewsletterService
{
    public function createNewsletter(array $data, ?UploadedFile $image = null): Newsletter
    {
        if ($image) {
            if (!$this->validateImage($image)) {
                throw new \InvalidArgumentException('Invalid newsletter image.');
            }
            $data['image_path'] = $this->uploadImage($image);
            $data['image_filename'] = $image->getClientOriginalName();
        }

        $data['slug'] = Newsletter::generateSlug($data['title']);
        return Newsletter::create($data);
    }

    public function updateNewsletter(Newsletter $newsletter, array $data, ?UploadedFile $image = null): Newsletter
    {
        if ($image) {
            if (!$this->validateImage($image)) {
                throw new \InvalidArgumentException('Invalid newsletter image.');
            }
            $this->deleteImageFile($newsletter->image_path);
            $data['image_path'] = $this->uploadImage($image);
            $data['image_filename'] = $image->getClientOriginalName();
        }

        $newsletter->update($data);
        return $newsletter->fresh();
    }

    public function deleteNewsletter(Newsletter $newsletter): bool
    {
        if ($newsletter->image_path) {
            $this->deleteImageFile($newsletter->image_path);
        }

        return (bool) $newsletter->delete();
    }

    public function restoreNewsletter(Newsletter $newsletter): bool
    {
        return (bool) $newsletter->restore();
    }

    public function publishNewsletter(Newsletter $newsletter): bool
    {
        return $newsletter->publish();
    }

    public function unpublishNewsletter(Newsletter $newsletter): bool
    {
        return $newsletter->unpublish();
    }

    public function listNewslettersForAdmin(int $perPage = 15, ?string $query = null): LengthAwarePaginator
    {
        return $this->adminQuery($query)->latest()->paginate($perPage)->withQueryString();
    }

    public function listPublishedNewsletters(int $perPage = 20): LengthAwarePaginator
    {
        return Newsletter::published()->latest()->paginate($perPage);
    }

    public function getRelatedNewsletters(Newsletter $newsletter, int $limit = 4)
    {
        return Newsletter::published()->whereKeyNot($newsletter->id)->latest()->limit($limit)->get();
    }

    public function uploadImage(UploadedFile $file): string
    {
        return $file->store('newsletters/images', 'public');
    }

    public function deleteImageFile(?string $path): bool
    {
        return !$path || !Storage::disk('public')->exists($path) || Storage::disk('public')->delete($path);
    }

    public function validateImage(UploadedFile $file): bool
    {
        return $file->getSize() <= 5 * 1024 * 1024
            && in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true);
    }

    private function adminQuery(?string $query = null)
    {
        return Newsletter::forAdmin()
            ->with('creator')
            ->when($query, function ($builder, $query) {
                $builder->where(function ($search) use ($query) {
                    $search->where('title', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%");
                });
            });
    }
}
