<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\GalleryAlbumRequest;
use App\Models\GalleryAlbum;
use App\Models\GalleryImage;
use App\Services\GalleryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GalleryController extends Controller
{
    public function __construct(
        private readonly GalleryService $galleryService
    ) {
    }

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
        $this->galleryService->createAlbum($request);

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
        $this->galleryService->updateAlbum($request, $gallery);

        return redirect()->route('admin.gallery.edit', $gallery)->with('success', 'Gallery album updated successfully.');
    }

    public function destroy(GalleryAlbum $gallery): RedirectResponse
    {
        $this->galleryService->deleteAlbum($gallery);

        return redirect()->route('admin.gallery.index')->with('success', 'Gallery album deleted successfully.');
    }

    public function replaceImage(Request $request, GalleryImage $image): RedirectResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'max:10240'],
        ]);

        $this->galleryService->replaceImage($image, $request->file('image'));

        return back()->with('success', 'Image replaced successfully.');
    }

    public function deleteImage(GalleryImage $image): RedirectResponse
    {
        $this->galleryService->deleteImage($image);

        return back()->with('success', 'Image deleted successfully.');
    }
}
