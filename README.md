# Laravel PHP-Elixir

[![Latest Stable Version](https://poser.pugx.org/bluora/laravel-php-elixir/v/stable.svg)](https://packagist.org/packages/bluora/laravel-php-elixir) [![Total Downloads](https://poser.pugx.org/bluora/laravel-php-elixir/downloads.svg)](https://packagist.org/packages/bluora/laravel-php-elixir) [![Latest Unstable Version](https://poser.pugx.org/bluora/laravel-php-elixir/v/unstable.svg)](https://packagist.org/packages/bluora/laravel-php-elixir) [![License](https://poser.pugx.org/bluora/laravel-php-elixir/license.svg)](https://packagist.org/packages/bluora/laravel-php-elixir)

[![Build Status](https://travis-ci.org/bluora/laravel-php-elixir.svg?branch=master)](https://travis-ci.org/bluora/laravel-php-elixir) [![StyleCI](https://styleci.io/repos/69619219/shield?branch=master)](https://styleci.io/repos/69619219) [![Test Coverage](https://codeclimate.com/github/bluora/laravel-php-elixir/badges/coverage.svg)](https://codeclimate.com/github/bluora/laravel-php-elixir/coverage) [![Issue Count](https://codeclimate.com/github/bluora/laravel-php-elixir/badges/issue_count.svg)](https://codeclimate.com/github/bluora/laravel-php-elixir) [![Code Climate](https://codeclimate.com/github/bluora/laravel-php-elixir/badges/gpa.svg)](https://codeclimate.com/github/bluora/laravel-php-elixir) 

Provides a replacement of the Node.js based elixir pre-packaged with Laravel Framework.

Tasks are sequentially run in the order that they are declared in the configuration file. You can specify more than one task module block.

## Available task modules

* Empty - removes all files and folders in the directory.
* Combine - combine a number of files into a single file.
* Copy - copy a file or files (current directory or all files in directory).
* Replace - replace specific text in a file or files.
* Revision - revision files and store in a rev-manifest.json file.
* SASS - process and compile a scss file.

## Task modules for future releases

* Exec - execute an external shell command.
* Less - compile less css files.

## Watcher

You can run a watcher on any number of files and folders to automatically run elixir on changes.

You can list these files or folders in the config section 'watch'. 

Run 'php artisan elixir:watch' to start up the watcher. It provides some verbose when a path watcher is added or removed, and when the standard elixir task is run.

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

## Configuration

Configuration for this package is done in the '.elixir.yml' located in your base directory or in the 'vendor/bluora/laravel-php-elixir/src/.elixir.yml.example'.

You can list tasks using the first level in the YAML file or, if you need to declare more than one task module in different location of the config file, simply prepend the task with a number (unique to the task) and a hash.

```yaml
copy:
    xxx: yyy
1#copy:
    zzz: aaa
```

If you are testing php-elixir, you can stop tasks from running by prepending them with an exclaimation mark (!).

```yaml
!copy:
    xxx: yyy
```

### Options

* dry-run - runs the script but doesn't actually do anything.
* verbose - provides further feedback of what is happening.

```yaml
options:
    dry-run: true
    verbose: true
```
### Paths

Paths lets you declare path constants that can be used in your other tasks.

```yaml
paths:
    PATH_SASS: resources/assets/sass
    PATH_BOWER: bower_components
    PATH_PUBLIC_ASSETS: public/assets
    PATH_PUBLIC_BUILD: public/build
    PATH_RESOURCES: resources
    PATH_RES_ASSET_IMAGES: resources/assets/images
```

### Watch

Watch configuruation item is only used by the elixir:watch console command.

```yaml
watch:
    - PATH_RESOURCES
```

### Empty

Deletes all files and folders in the listed paths.

```yaml
empty:
    - PATH_PUBLIC_ASSETS
    - PATH_PUBLIC_BUILD
```

### SASS

Processes and compiles a *.scss file and outputs it to specified path.

Formatted as: {SOURCE_FILE_PATH}: {DESTINATION_FILE_PATH}

```yaml
sass:
    PATH_SASS + /app.scss: PATH_PUBLIC_ASSETS + /vendor/app.css
```

### Combine

Gets the contents of files in one or many files or folders and combines it into the specified file.

```
{DESTINATION_FILE}:
    - {SOURCE_FILE}
    - {SOURCE_FOLDER}
```

```yaml
combine:
    PATH_PUBLIC_ASSETS + /vendor/jquery-combined.min.js:
        - PATH_PUBLIC_ASSETS + /vendor/jquery/jquery.min.js
        - PATH_PUBLIC_ASSETS + /vendor/jquery-ui/jquery-ui.min.js
    PATH_PUBLIC_ASSETS + /vendor/combined.js:
        - PATH_PUBLIC_ASSETS + /vendor/**?filter=js

```

### Copy

Copies files from a file path or a folder path to a specified folder or file name.

Folder paths can be configured to get the top level directory using '/*' or for all files and folders in path by using '/**'.

Further configuration can be added using the standard query string format.

* filter - comma deliminated list of extensions.

```yaml
copy:
    PATH_BOWER + /jquery/dist/jquery.min.js: PATH_PUBLIC_ASSETS + /vendor/jquery/
    PATH_RES_ASSET_IMAGES + /**?filter=png: PATH_PUBLIC_ASSETS + /images/
```

### Replace

You can replace specific text in files or folder paths.

```yaml
replace:
    PATH_PUBLIC_ASSETS + /vendor/vendor_name/styles.css:
        - ../img
        - vendor/vendor_name
```

### Revision

Provides revisioning of files in a specified folder location.

Options that are available:

* hash_length - defaults is 8.
* minify - default is false.
* php_manifest - generates a php equivalent of the json revision file.

```
{SOURCE_FOLDER}:
    - {DESTINATION_FOLDER}
    - {REVISION_MANIFEST_FILE}
    - {QUERY_STRING_OPTIONS}
```

```yaml
revision:
    PATH_PUBLIC_ASSETS:
        - PATH_PUBLIC_BUILD
        - PATH_PUBLIC_BUILD + /rev-manifest.json
        - hash_length=12&minify=true&php_manifest=true
```

## License

The Laravel PHP-Elixir is open-sourced software licensed under the MIT license.
