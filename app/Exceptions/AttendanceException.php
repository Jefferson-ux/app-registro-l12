<?php

namespace App\Exceptions;

use Exception;
use Throwable;
use Override;

class AttendanceException extends Exception
{
    public function __construct(string $message = "Error", public int $status = 422)
    {
        parent::__construct($message);
    }
}
