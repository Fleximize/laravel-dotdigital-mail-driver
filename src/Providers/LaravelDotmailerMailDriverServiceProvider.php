<?php

declare(strict_types=1);

namespace Fleximize\LaravelDotmailerMailDriver\Providers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Fleximize\LaravelDotmailerMailDriver\DotmailerClient;
use Fleximize\LaravelDotmailerMailDriver\Enums\DotmailerEnum;
use Fleximize\LaravelDotmailerMailDriver\Mail\Transport\DotmailerTransport;

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

        config([
            sprintf('mail.mailers.%s', DotmailerEnum::DOTMAILER->value) => [
                'transport' => DotmailerEnum::DOTMAILER->value,
            ],
        ]);
    }
}
