# Laravel PHP-Elixir

[![StyleCI](https://styleci.io/repos/69619219/shield?branch=master)](https://styleci.io/repos/69619219) [![Build Status](https://travis-ci.org/bluora/laravel-php-elixir.svg?branch=master)](https://travis-ci.org/bluora/laravel-php-elixir)

Adds a replacement of the node.js based elixir pre-packaged with Laravel Framework.

## Installation

Install via composer:

`composer require bluora/laravel-php-elixir dev-master`

Add it to your available console commands in app/Console/Kernel.php:

```php
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
      ...
      \PhpElixir\ElixirConsoleCommand::class,
      \PhpElixir\ElixirWatchCommand::class,
    ];
```

Run  'php artisan elixir' to copy the default config file which will copy to your base folder as '.elixir.yml'.

