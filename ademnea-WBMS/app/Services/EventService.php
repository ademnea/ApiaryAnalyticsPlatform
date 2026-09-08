<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class EventService
{
    /**
     * Create a new event record with photos
     */
    public function createEvent(array $data, array $photoFiles = []): Event
    {
        // Validate and upload photos if provided
        if (!empty($photoFiles)) {
            $uploadedPaths = $this->uploadEventPhotos($photoFiles);
            $data['photos'] = $uploadedPaths;
        }

        // Generate slug
        $data['slug'] = Event::generateSlug($data['title']);

        $event = Event::create($data);

        // Link photos to event
        if (isset($data['photos'])) {
            foreach ($data['photos'] as $index => $photoPath) {
                EventPhoto::create([
                    'event_id' => $event->id,
                    'photo_path' => $photoPath['path'],
                    'photo_filename' => $photoPath['filename'],
                    'photo_order' => $index,
                ]);
            }
        }

        return $event;
    }

    /**
     * Update an existing event with optional new photos
     */
    public function updateEvent(Event $event, array $data, array $photoFiles = []): Event
    {
        // Handle new photos if provided
        if (!empty($photoFiles)) {
            $uploadedPaths = $this->uploadEventPhotos($photoFiles);
            
            // Get max photo_order
            $maxOrder = $event->photos()->max('photo_order') ?? -1;
            
            // Add new photos with incremented order
            foreach ($uploadedPaths as $index => $photoPath) {
                EventPhoto::create([
                    'event_id' => $event->id,
                    'photo_path' => $photoPath['path'],
                    'photo_filename' => $photoPath['filename'],
                    'photo_order' => $maxOrder + $index + 1,
                ]);
            }
        }

        $event->update($data);
        return $event;
    }

    /**
     * Soft delete an event and its photos
     */
    public function deleteEvent(Event $event): bool
    {
        // Delete all photo files
        foreach ($event->photos as $photo) {
            if (Storage::exists($photo->photo_path)) {
                Storage::delete($photo->photo_path);
            }
        }

        // Soft delete the event (photos cascade deleted)
        return $event->delete();
    }

    /**
     * Restore a soft-deleted event
     */
    public function restoreEvent(Event $event): bool
    {
        return $event->restore();
    }

    /**
     * Publish an event
     */
    public function publishEvent(Event $event): bool
    {
        return $event->publish();
    }

    /**
     * Unpublish an event
     */
    public function unpublishEvent(Event $event): bool
    {
        return $event->unpublish();
    }

    /**
     * Add photos to an existing event
     */
    public function addPhotosToEvent(Event $event, array $photoFiles): array
    {
        $uploadedPaths = $this->uploadEventPhotos($photoFiles);
        
        // Get max photo_order
        $maxOrder = $event->photos()->max('photo_order') ?? -1;
        
        $createdPhotos = [];
        foreach ($uploadedPaths as $index => $photoPath) {
            $photo = EventPhoto::create([
                'event_id' => $event->id,
                'photo_path' => $photoPath['path'],
                'photo_filename' => $photoPath['filename'],
                'photo_order' => $maxOrder + $index + 1,
            ]);
            $createdPhotos[] = $photo;
        }

        return $createdPhotos;
    }

    /**
     * Delete a specific event photo
     */
    public function deleteEventPhoto(EventPhoto $photo): bool
    {
        // Delete file from storage
        if (Storage::exists($photo->photo_path)) {
            Storage::delete($photo->photo_path);
        }

        return $photo->delete();
    }

    /**
     * Reorder photos for an event
     */
    public function reorderPhotos(Event $event, array $photoIds): bool
    {
        foreach ($photoIds as $index => $photoId) {
            EventPhoto::where('id', $photoId)
                ->where('event_id', $event->id)
                ->update(['photo_order' => $index]);
        }

        return true;
    }

    /**
     * Search events by title, venue, or date
     */
    public function searchEvents(string $query, int $perPage = 20): LengthAwarePaginator
    {
        return Event::where('title', 'LIKE', "%{$query}%")
            ->orWhere('venue', 'LIKE', "%{$query}%")
            ->forAdmin()
            ->latest()
            ->paginate($perPage);
    }

    /**
     * List all events for admin (including soft deleted)
     */
    public function listEventsForAdmin(int $perPage = 12): LengthAwarePaginator
    {
        return Event::forAdmin()
            ->latest()
            ->paginate($perPage);
    }

    /**
     * List published events for public view
     */
    public function listPublishedEvents(int $perPage = 15, string $sortBy = 'event_date'): LengthAwarePaginator
    {
        return Event::published()
            ->orderBy($sortBy, 'desc')
            ->paginate($perPage);
    }

    /**
     * Get upcoming events
     */
    public function getUpcomingEvents(int $limit = 10): Collection
    {
        return Event::published()
            ->upcoming()
            ->orderBy('event_date', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get past events (paginated)
     */
    public function getPastEvents(int $perPage = 15): LengthAwarePaginator
    {
        return Event::published()
            ->past()
            ->orderBy('event_date', 'desc')
            ->paginate($perPage);
    }

    /**
     * Upload and store multiple event photos
     */
    public function uploadEventPhotos(array $files): array
    {
        $uploadedPhotos = [];

        foreach ($files as $file) {
            if ($this->validatePhoto($file)) {
                $path = $this->uploadPhoto($file);
                $uploadedPhotos[] = [
                    'path' => $path,
                    'filename' => $file->getClientOriginalName(),
                ];
            }
        }

        return $uploadedPhotos;
    }

    /**
     * Upload and store a single photo
     */
    public function uploadPhoto(UploadedFile $file): string
    {
        return $file->store('events/photos', 'public');
    }

    /**
     * Delete a photo file from storage
     */
    public function deletePhotoFile(string $path): bool
    {
        if (Storage::exists($path)) {
            return Storage::delete($path);
        }
        return true;
    }

    /**
     * Validate photo file
     * Allowed: JPG, JPEG, PNG, WEBP, GIF
     * Max: 5 MB
     */
    public function validatePhoto(UploadedFile $file): bool
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
}
