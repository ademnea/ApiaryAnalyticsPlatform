<?php

namespace App\Events\ApiaryManagement;

use App\Models\Hive;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HiveRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(public Hive $hive)
    {
    }
}
