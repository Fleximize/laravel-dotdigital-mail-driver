<?php

declare(strict_types=1);

namespace Samharvey\LaravelDotmailerMailDriver\Exceptions;

use Exception;

class MissingDotmailerCredentialsException extends Exception
{
    public function __construct()
    {
        parent::__construct('Dotmailer credentials are missing. Please ensure you have set DOTMAILER_USERNAME and DOTMAILER_PASSWORD in your .env file.');
    }
}
