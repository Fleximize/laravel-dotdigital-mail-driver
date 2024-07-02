<?php

declare(strict_types=1);

namespace Fleximize\LaravelDotmailerMailDriver\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

class DotmailerRequestFailedException extends Exception
{
    public function __construct(Response $dotmailerResponse)
    {
        parent::__construct(
            sprintf(
                'Dotmailer request failed with status code %s and body: %s',
                $dotmailerResponse->status(),
                $dotmailerResponse->body() ?: '[NO CONTENT]'
            )
        );
    }
}
