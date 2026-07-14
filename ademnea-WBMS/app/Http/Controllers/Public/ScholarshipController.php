<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Scholarship;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScholarshipController extends Controller
{
    public function index(Request $request): View
    {
        $query = Scholarship::query()
            ->where('status', 'active')
            ->withCount('attachments')
            ->latest();

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', $search)
                    ->orWhere('institution', 'like', $search)
                    ->orWhere('country', 'like', $search)
                    ->orWhere('category', 'like', $search);
            });
        }

        if ($request->filled('country')) {
            $query->where('country', $request->country);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('funding_type')) {
            $query->where('funding_type', $request->funding_type);
        }

        $scholarships = $query->paginate(12)->appends($request->only(['search', 'country', 'category', 'funding_type']));

        $countries = Scholarship::query()->where('status', 'active')->select('country')->distinct()->pluck('country');
        $categories = Scholarship::query()->where('status', 'active')->select('category')->distinct()->pluck('category');
        $fundingTypes = Scholarship::query()->where('status', 'active')->select('funding_type')->distinct()->pluck('funding_type');

        return view('public.scholarships.index', compact('scholarships', 'countries', 'categories', 'fundingTypes'));
    }

    public function show(Scholarship $scholarship): View
    {
        abort_unless($scholarship->status === 'active', 404);

        $scholarship->load('attachments');

        return view('public.scholarships.show', compact('scholarship'));
    }
}
