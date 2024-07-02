<?php

declare(strict_types=1);

namespace Fleximize\LaravelDotdigitalMailDriver\Exceptions;

use Exception;

class MissingDotdigitalCredentialsException extends Exception
{
    public function __construct()
    {
        parent::__construct('Dotdigital credentials are missing. Please ensure you have set DOTDIGITAL_USERNAME and DOTDIGITAL_PASSWORD in your .env file.');
    }
}
