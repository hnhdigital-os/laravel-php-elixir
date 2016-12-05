<?php

namespace Bluora\PhpElixir;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ElixirConsoleCommand::class,
                ElixirWatchCommand::class,
            ]);
        }
    }
}
