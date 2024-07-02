<?php

declare(strict_types=1);

namespace Fleximize\LaravelDotdigitalMailDriver\Exceptions;

use Exception;

class MissingDotdigitalRegionException extends Exception
{
    public function __construct()
    {
        parent::__construct('Dotdigital region is missing. Please ensure you have set DOTDIGITAL_REGION in your .env file.');
    }
}
