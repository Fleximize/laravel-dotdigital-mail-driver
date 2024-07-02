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
