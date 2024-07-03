# Dotdigital Mail Driver for Laravel

<hr />

This package simply extends Laravel's Mailer to provide a new Dotdigital 
transport that is registered under the `dotdigital` driver.

Usage is very simple - send a Mailable the same way you would for any other driver,
but specify the `dotdigital` driver in the `mail` method.

```php
Mail::driver('dotdigital')->send(new MyMailable());
```

Under the hood, this will use the `dotdigital` transactional email API to 
send the email.

<hr />

## Requirements

- Laravel 10.x 
- PHP 8.2 or higher

<hr />

## Installation

You can install the package via composer:

```bash
composer require fleximize/laravel-dotdigital-mail-driver
```

Once installed, publish the configuration file:

```bash
php artisan vendor:publish --provider="Fleximize\LaravelDotdigitalMailDriver\Providers\LaravelDotdigitalMailDriverServiceProvider"
```

This will create a `dotdigital.php` file in your `config` directory. Here, you will
need to specify the region, username, and password for your Dotdigital API user:

```php
<?php

return [
    'region' => env('DOTDIGITAL_REGION'),
    'username' => env('DOTDIGITAL_USERNAME'),
    'password' => env('DOTDIGITAL_PASSWORD'),
];
```

You can then set these values in your `.env` file:

```dotenv
DOTDIGITAL_REGION=r1
DOTDIGITAL_USERNAME=my-username
DOTDIGITAL_PASSWORD=my-password
```

The package will automatically register the `dotdigital` driver within your `config/mail.php` file.

