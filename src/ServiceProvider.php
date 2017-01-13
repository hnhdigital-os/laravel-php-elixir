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
            ]);
        }

        // Publish the default config.
        $this->publishes([
            __DIR__.'/.elixir.yml.example' => base_path('.elixir.yml'),
        ]);
    }
}
