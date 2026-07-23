<?php

namespace App\Services;

use App\Http\Requests\GalleryAlbumRequest;
use App\Models\GalleryAlbum;
use App\Models\GalleryImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class GalleryService
{
    public function createAlbum(GalleryAlbumRequest $request): GalleryAlbum
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        $data['is_published'] = $request->boolean('is_published');
        $data['visibility'] = $request->input('visibility', 'public');
        $data['category'] = $request->input('category');

        $album = GalleryAlbum::create($data);

        if ($request->hasFile('cover_image')) {
            $album->cover_image = $request->file('cover_image')->store('gallery/covers', 'public');
            $album->save();
        }

        if ($request->hasFile('images')) {
            $this->storeImages($album, $request->file('images'));

            // If no separate cover image was uploaded, allow selecting one from the batch
            if (!$album->cover_image && $request->filled('cover_image_index')) {
                $index = (int) $request->input('cover_image_index');
                $images = $album->images()->orderBy('order')->orderBy('id')->get();
                if (isset($images[$index])) {
                    $album->cover_image = $images[$index]->path;
                    $album->save();
                }
            }
        }

        return $album;
    }

    public function updateAlbum(GalleryAlbumRequest $request, GalleryAlbum $gallery): GalleryAlbum
    {
        $data = $request->validated();
        $data['is_published'] = $request->boolean('is_published');
        $data['visibility'] = $request->input('visibility', 'public');
        $data['category'] = $request->input('category');

        if ($request->hasFile('cover_image')) {
            if ($gallery->cover_image) {
                Storage::disk('public')->delete($gallery->cover_image);
            }

            $data['cover_image'] = $request->file('cover_image')->store('gallery/covers', 'public');
        }

        $gallery->update($data);

        if ($request->hasFile('images')) {
            $this->storeImages($gallery, $request->file('images'));
        }

        if ($request->filled('deleted_images')) {
            foreach ($request->input('deleted_images', []) as $imageId) {
                $image = $gallery->images()->find($imageId);
                if ($image) {
                    $this->deleteImage($image);
                }
            }
        }

        if ($request->filled('ordered_ids')) {
            $this->reorderImages($gallery, $request->input('ordered_ids', []));
        }

        return $gallery;
    }

    public function deleteAlbum(GalleryAlbum $gallery): void
    {
        foreach ($gallery->images as $image) {
            Storage::disk('public')->delete($image->path);
        }

        if ($gallery->cover_image) {
            Storage::disk('public')->delete($gallery->cover_image);
        }

        $gallery->delete();
    }

    public function replaceImage(GalleryImage $image, UploadedFile $file): GalleryImage
    {
        if ($image->path) {
            Storage::disk('public')->delete($image->path);
        }

        $image->path = $file->store('gallery/images', 'public');
        $image->file_name = $file->getClientOriginalName();
        $image->mime_type = $file->getClientMimeType();
        $image->save();

        return $image;
    }

    public function deleteImage(GalleryImage $image): void
    {
        Storage::disk('public')->delete($image->path);
        $image->delete();
    }

    public function reorderImages(GalleryAlbum $album, array $orderedIds): void
    {
        $images = $album->images()->get()->keyBy('id');
        $order = 0;

        foreach ($orderedIds as $id) {
            if (isset($images[$id])) {
                $images[$id]->update(['order' => $order++]);
            }
        }
    }

    protected function storeImages(GalleryAlbum $album, array $files): void
    {
        // Filter out any null slots that browsers may include in the array.
        $files = array_filter($files, fn ($f) => $f !== null);
        $order = $album->images()->count();

        foreach ($files as $file) {
            $path = $file->store('gallery/images', 'public');

            $album->images()->create([
                'file_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'order' => $order,
            ]);

            $order++;
        }
    }
}
