<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GalleryAlbumRequest;
use App\Models\GalleryAlbum;
use App\Models\GalleryImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GalleryController extends Controller
{
    public function index(Request $request): View
    {
        $query = GalleryAlbum::query()->withCount('images')->latest();

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', $search)
                    ->orWhere('description', 'like', $search);
            });
        }

        if ($request->filled('status')) {
            $query->where('is_published', $request->status === 'published');
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('visibility')) {
            $query->where('visibility', $request->visibility);
        }

        $albums = $query->paginate(10)->appends($request->only(['search', 'status', 'category', 'visibility']));

        $stats = [
            'totalAlbums' => GalleryAlbum::count(),
            'totalImages' => GalleryImage::count(),
            'publishedAlbums' => GalleryAlbum::where('is_published', true)->count(),
            'totalViews' => GalleryAlbum::sum('views'),
        ];

        $categories = GalleryAlbum::CATEGORIES;
        $visibilityOptions = GalleryAlbum::VISIBILITY_OPTIONS;
        $recentAlbums = GalleryAlbum::latest()->limit(5)->get();

        return view('admin.gallery.index', compact('albums', 'stats', 'categories', 'visibilityOptions', 'recentAlbums'));
    }

    public function create(): View
    {
        $categories = GalleryAlbum::CATEGORIES;
        $visibilityOptions = GalleryAlbum::VISIBILITY_OPTIONS;

        return view('admin.gallery.create', compact('categories', 'visibilityOptions'));
    }

    public function store(GalleryAlbumRequest $request): RedirectResponse
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
        }

        return redirect()->route('admin.gallery.index')->with('success', 'Gallery album created successfully.');
    }

    public function edit(GalleryAlbum $gallery): View
    {
        $gallery->load('images');
        $categories = GalleryAlbum::CATEGORIES;
        $visibilityOptions = GalleryAlbum::VISIBILITY_OPTIONS;

        return view('admin.gallery.edit', compact('gallery', 'categories', 'visibilityOptions'));
    }

    public function update(GalleryAlbumRequest $request, GalleryAlbum $gallery): RedirectResponse
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

        return redirect()->route('admin.gallery.edit', $gallery)->with('success', 'Gallery album updated successfully.');
    }

    public function destroy(GalleryAlbum $gallery): RedirectResponse
    {
        foreach ($gallery->images as $image) {
            Storage::disk('public')->delete($image->path);
        }

        if ($gallery->cover_image) {
            Storage::disk('public')->delete($gallery->cover_image);
        }

        $gallery->delete();

        return redirect()->route('admin.gallery.index')->with('success', 'Gallery album deleted successfully.');
    }

    public function replaceImage(Request $request, GalleryImage $image): RedirectResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($image->path) {
            Storage::disk('public')->delete($image->path);
        }

        $image->path = $request->file('image')->store('gallery/images', 'public');
        $image->file_name = $request->file('image')->getClientOriginalName();
        $image->mime_type = $request->file('image')->getClientMimeType();
        $image->save();

        return back()->with('success', 'Image replaced successfully.');
    }

    public function deleteImage(GalleryImage $image): RedirectResponse
    {
        Storage::disk('public')->delete($image->path);
        $image->delete();

        return back()->with('success', 'Image deleted successfully.');
    }

    protected function storeImages(GalleryAlbum $album, array $files): void
    {
        $files = array_filter(is_array($files) ? $files : [$files]);
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
