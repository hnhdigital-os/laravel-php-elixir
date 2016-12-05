<?php

namespace Bluora\PhpElixir\Modules;

use Bluora\PhpElixir\AbstractModule;
use Bluora\PhpElixir\ElixirWatchCommand as Elixir;

class WatchModule extends AbstractModule
{
    /**
     * Verify the configuration for this task.
     *
     * @param mixed $path
     *
     * @return bool
     */
    public static function verify($index, $path)
    {
        return Elixir::checkPath($path, false, true);
    }
}
