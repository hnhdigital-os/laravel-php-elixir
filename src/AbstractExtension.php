<?php

namespace PhpElixir;

abstract class AbstractExtension
{
    /**
     * Verify the configuration for this task.
     *
     * @param  string $key
     * @param  mixed $settings
     * @return boolean
     */
    public static function verify($key, $settings)
    {
        return true;
    }

    /**
     * Run the task.
     *
     * @param  string $key
     * @param  mixed $settings
     * @return boolean
     */
    public function run($key, $settings)
    {
        return true;
    }
}
