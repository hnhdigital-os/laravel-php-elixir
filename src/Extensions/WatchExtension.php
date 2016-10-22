<?php

namespace PhpElixir\Extensions;

use PhpElixir\AbstractExtension;
use PhpElixir\ElixirWatchCommand as Elixir;

class WatchExtension extends AbstractExtension
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
