<?php

declare(strict_types=1);

namespace Fleximize\LaravelDotmailerMailDriver\Exceptions;

use Exception;

class MissingDotmailerRegionException extends Exception
{
    public function __construct()
    {
        parent::__construct('Dotmailer region is missing. Please ensure you have set DOTMAILER_REGION in your .env file.');
    }
}
