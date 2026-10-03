<?php

namespace App\Http\Controllers\Api\Farmer\Concerns;

use App\Models\Farmer;
use Illuminate\Http\Request;

/**
 * Resolves the Farmer profile for the authenticated token holder.
 *
 * The token authenticates a User; farmer-owned data is keyed by farmers.id.
 * Passing the user id straight into a farmer_id column silently reads and
 * writes another farmer's rows whenever the two sequences differ, which is
 * exactly the bug this trait exists to make impossible.
 */
trait ResolvesFarmer
{
    private ?Farmer $resolvedFarmer = null;

    protected function farmer(Request $request): Farmer
    {
        if ($this->resolvedFarmer !== null) {
            return $this->resolvedFarmer;
        }

        $farmer = Farmer::where('user_id', $request->user()->id)->first();

        abort_if($farmer === null, 404, 'Farmer profile not found.');

        return $this->resolvedFarmer = $farmer;
    }

    protected function farmerId(Request $request): int
    {
        return $this->farmer($request)->id;
    }
}
