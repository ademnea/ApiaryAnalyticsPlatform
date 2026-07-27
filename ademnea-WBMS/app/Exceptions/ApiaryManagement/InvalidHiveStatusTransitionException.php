<?php

namespace App\Exceptions\ApiaryManagement;

use RuntimeException;

class InvalidHiveStatusTransitionException extends RuntimeException
{
    public static function notAllowed(string $from, string $to): self
    {
        return new self("Hive cannot transition from \"{$from}\" to \"{$to}\".");
    }
}
