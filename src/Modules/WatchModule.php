<?php

namespace PhpElixir\Modules;

use PhpElixir\AbstractModule;
use PhpElixir\ElixirWatchCommand as Elixir;

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
