<?php

namespace App\Http\Controllers\Api\Farmer\Concerns;

use App\Models\Farmer;
use Illuminate\Http\Request;

trait ResolvesFarmer
{
    /**
     * The Farmer profile of the authenticated API user (404 if none is linked).
     * Use $farmer->id, never $request->user()->id, for farmer-owned records.
     */
    protected function currentFarmer(Request $request): Farmer
    {
        return Farmer::where('user_id', $request->user()->id)->firstOrFail();
    }
}
