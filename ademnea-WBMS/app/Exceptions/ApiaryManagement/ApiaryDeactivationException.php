<?php

namespace App\Exceptions\ApiaryManagement;

use RuntimeException;

class ApiaryDeactivationException extends RuntimeException
{
    public static function hasActiveHives(int $apiaryId, int $activeHiveCount): self
    {
        return new self(
            "Apiary #{$apiaryId} cannot be deactivated: it has {$activeHiveCount} active hive(s). ".
            'Transition or decommission the hives first.'
        );
    }
}
