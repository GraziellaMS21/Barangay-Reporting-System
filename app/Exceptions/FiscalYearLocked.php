<?php

namespace App\Exceptions;

use RuntimeException;

class FiscalYearLocked extends RuntimeException
{
    public function __construct(string $message = 'The fiscal year is locked and cannot be modified.')
    {
        parent::__construct($message);
    }
}
