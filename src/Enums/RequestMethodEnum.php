<?php

declare(strict_types=1);

namespace Fleximize\LaravelDotmailerMailDriver\Enums;

enum RequestMethodEnum
{
    case GET;
    case POST;
    case PUT;
    case DELETE;
    case PATCH;
}
