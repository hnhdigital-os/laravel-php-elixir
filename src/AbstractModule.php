<?php

namespace PhpElixir;

abstract class AbstractModule
{
    /**
     * Verify the configuration for this task.
     *
     * @return bool
     */
    public static function verify()
    {
        return true;
    }

    /**
     * Run the task.
     *
     * @return bool
     */
    public function run()
    {
        return true;
    }
}
