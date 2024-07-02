<?php

declare(strict_types=1);

namespace Samharvey\LaravelDotmailerMailDriver\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Samharvey\LaravelDotmailerMailDriver\DotmailerClient;
use Samharvey\LaravelDotmailerMailDriver\Enums\DotmailerEnum;
use Samharvey\LaravelDotmailerMailDriver\Mail\Transport\DotmailerTransport;

class LaravelDotmailerMailDriverServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            DotmailerClient::class,
            function () {
                return new DotmailerClient();
            }
        );
    }

    public function boot(): void
    {
        $this->publishes(
            [
                __DIR__.'/../config/dotmailer.php' => config_path('dotmailer.php'),
            ],
        );

        Mail::extend(DotmailerEnum::DOTMAILER->value, fn (array $config) => new DotmailerTransport(
            app(DotmailerClient::class),
        ));
    }
}
