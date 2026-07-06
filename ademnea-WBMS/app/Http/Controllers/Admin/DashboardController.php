<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class DashboardController extends Controller
{
    /** Show simple dashboard overview. */
    public function index(Request $request)
    {
        // Basic stats for initial skeleton
        $stats = [
            'users' => User::count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
