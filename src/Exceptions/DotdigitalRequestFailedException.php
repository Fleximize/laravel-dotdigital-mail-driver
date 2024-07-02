<?php

declare(strict_types=1);

namespace Fleximize\LaravelDotdigitalMailDriver\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

class DotdigitalRequestFailedException extends Exception
{
    public function __construct(Response $dotdigitalResponse)
    {
        parent::__construct(
            sprintf(
                'Dotdigital request failed with status code %s and body: %s',
                $dotdigitalResponse->status(),
                $dotdigitalResponse->body() ?: '[NO CONTENT]'
            )
        );
    }
}
