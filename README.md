# Laravel PHP-Elixir

[![Latest Stable Version](https://poser.pugx.org/bluora/laravel-php-elixir/v/stable.svg)](https://packagist.org/packages/bluora/laravel-php-elixir) [![Total Downloads](https://poser.pugx.org/bluora/laravel-php-elixir/downloads.svg)](https://packagist.org/packages/bluora/laravel-php-elixir) [![Latest Unstable Version](https://poser.pugx.org/bluora/laravel-php-elixir/v/unstable.svg)](https://packagist.org/packages/bluora/laravel-php-elixir) [![License](https://poser.pugx.org/bluora/laravel-php-elixir/license.svg)](https://packagist.org/packages/bluora/laravel-php-elixir)

[![Build Status](https://travis-ci.org/bluora/laravel-php-elixir.svg?branch=master)](https://travis-ci.org/bluora/laravel-php-elixir) [![StyleCI](https://styleci.io/repos/69619219/shield?branch=master)](https://styleci.io/repos/69619219) [![Test Coverage](https://codeclimate.com/github/bluora/laravel-php-elixir/badges/coverage.svg)](https://codeclimate.com/github/bluora/laravel-php-elixir/coverage) [![Issue Count](https://codeclimate.com/github/bluora/laravel-php-elixir/badges/issue_count.svg)](https://codeclimate.com/github/bluora/laravel-php-elixir) [![Code Climate](https://codeclimate.com/github/bluora/laravel-php-elixir/badges/gpa.svg)](https://codeclimate.com/github/bluora/laravel-php-elixir) 

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

