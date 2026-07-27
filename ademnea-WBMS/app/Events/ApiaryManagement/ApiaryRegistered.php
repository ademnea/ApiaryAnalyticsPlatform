<?php

namespace App\Events\ApiaryManagement;

use App\Models\Apiary;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApiaryRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(public Apiary $apiary)
    {
    }
}
