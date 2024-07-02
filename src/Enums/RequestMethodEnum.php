<?php

declare(strict_types=1);

namespace Samharvey\LaravelDotmailerMailDriver\Enums;

enum RequestMethodEnum
{
    case GET;
    case POST;
    case PUT;
    case DELETE;
    case PATCH;
}
