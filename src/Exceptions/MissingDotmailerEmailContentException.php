<?php

declare(strict_types=1);

namespace Samharvey\LaravelDotmailerMailDriver\Exceptions;

use Exception;

class MissingDotmailerEmailContentException extends Exception
{
    public function __construct()
    {
        parent::__construct('Email content is missing. Please ensure you have set the HTML or text content for your email.');
    }
}
