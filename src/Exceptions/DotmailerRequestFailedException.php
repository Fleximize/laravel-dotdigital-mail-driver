<?php

declare(strict_types=1);

namespace Samharvey\LaravelDotmailerMailDriver\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

class DotmailerRequestFailedException extends Exception
{
    public function __construct(Response $dotmailerResponse)
    {
        parent::__construct(
            sprintf(
                'Dotmailer request failed with status code %s and response: %s',
                $dotmailerResponse->getStatusCode(),
                $dotmailerResponse->getContent() ?: '[NO CONTENT]'
            )
        );
    }
}
