<?php

namespace App\Exceptions;

use RuntimeException;

class NoActiveFiscalYear extends RuntimeException
{
    public function __construct(string $message = 'No active fiscal year is configured in the system.')
    {
        parent::__construct($message);
    }
}
