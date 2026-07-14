<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\GalleryAlbum;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GalleryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = GalleryAlbum::CATEGORIES;
        $selectedCategory = $request->input('category');

        $query = GalleryAlbum::query()
            ->where('is_published', true)
            ->where('visibility', 'public')
            ->withCount('images')
            ->with('images')
            ->latest();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $albums = $query->paginate(9)->appends($request->only('category'));

        return view('public.gallery.index', compact('albums', 'categories', 'selectedCategory'));
    }

    public function show(GalleryAlbum $gallery): View
    {
        abort_unless($gallery->is_published && $gallery->visibility === 'public', 404);

        $gallery->load('images');
        $gallery->increment('views');

        return view('public.gallery.show', compact('gallery'));
    }
}
