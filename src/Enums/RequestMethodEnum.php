<?php

declare(strict_types=1);

namespace Fleximize\LaravelDotdigitalMailDriver\Enums;

enum RequestMethodEnum
{
    case GET;
    case POST;
    case PUT;
    case DELETE;
    case PATCH;
}
