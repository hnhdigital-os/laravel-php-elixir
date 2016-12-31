<?php

namespace Bluora\PhpElixir;

abstract class AbstractModule
{
    /**
     * Verify the configuration for this task.
     *
     * @param mixed $parameter1
     * @param mixed $parameter2
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function verify($parameter1, $parameter2)
    {
        return true;
    }

    /**
     * Run the task.
     *
     * @param mixed $parameter1
     * @param mixed $parameter2
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function run($parameter1, $parameter2)
    {
        return true;
    }
}
