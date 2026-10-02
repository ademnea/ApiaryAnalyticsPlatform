<?php

namespace App\Http\Controllers\Api\Farmer\Concerns;

use Illuminate\Http\Request;

trait ClampsPageSize
{
    /**
     * ?per_page= bounded to 1..$max, so a client can't ask the server to load
     * an entire table into memory with ?per_page=1000000.
     */
    protected function pageSize(Request $request, int $default, int $max = 100): int
    {
        return max(1, min((int) $request->input('per_page', $default), $max));
    }
}
