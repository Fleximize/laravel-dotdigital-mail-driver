<?php

declare(strict_types=1);

namespace Fleximize\LaravelDotdigitalMailDriver\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Fleximize\LaravelDotdigitalMailDriver\DotdigitalClient;
use Fleximize\LaravelDotdigitalMailDriver\Enums\DotdigitalEnum;
use Fleximize\LaravelDotdigitalMailDriver\Mail\Transport\DotdigitalTransport;

class LaravelDotdigitalMailDriverServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DotdigitalClient::class, fn () => new DotdigitalClient());
    }

    public function boot(): void
    {
        $this->publishes(
            [
                __DIR__ . '/../config/dotdigital.php' => config_path('dotdigital.php'),
            ],
        );

        Mail::extend(DotdigitalEnum::DOTDIGITAL->value, fn (array $config) => new DotdigitalTransport(
            app(DotdigitalClient::class),
        ));

        config([
            sprintf('mail.mailers.%s', DotdigitalEnum::DOTDIGITAL->value) => [
                'transport' => DotdigitalEnum::DOTDIGITAL->value,
            ],
        ]);
    }
}
