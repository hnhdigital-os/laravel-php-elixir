<?php

namespace PhpElixir\Extensions;

use PhpElixir\AbstractExtension;
use PhpElixir\ElixirConsoleCommand as Elixir;

class EmptyExtension extends AbstractExtension
{
    /**
     * Verify the configuration for this task.
     *
     * @param  integer $index
     * @param  string $clear_path
     *
     * @return boolean
     */
    public static function verify($index, $clear_path)
    {
        if (!($scan_path = Elixir::checkPath($clear_path))) {
            Elixir::console()->error(sprintf('Path \'%s\' does not exist.', $clear_path));
            return false;
        }

        // Ensure that this folder is contained so it doesn't remove the project.
        if (stripos($scan_path, public_path()) === false || $scan_path == public_path()) {
            Elixir::console()->error(sprintf('Clearing files from \'%s\' is unwise.', $scan_path));
            return false;
        }

        return true;
    }

    /**
     * Run the task.
     *
     * @param  integer $index
     * @param  string $clear_path
     *
     * @return boolean
     */
    public function run($index, $clear_path)
    {
        Elixir::commandInfo('Running \'empty\' extension...');
        Elixir::console()->line('');
        Elixir::console()->info('   Clearing files and folders from...');
        Elixir::console()->line(sprintf(' - %s', $clear_path));
        Elixir::console()->line('');

        return $this->process($index, $clear_path);
    }

    /**
     * Process the task.
     *
     * @param  integer $index
     * @param  string $clear_path
     *
     * @return boolean
     */
    private function process($index, $clear_path)
    {
        $scan_path = Elixir::checkPath($clear_path);
        (substr($scan_path, -1) !== '/') ? $scan_path .= '/' : false;

        $paths = Elixir::scan($scan_path);

        Elixir::console()->info(sprintf('   Found %s folders and files. Removing...', count($paths)));
        Elixir::console()->line('');
        
        foreach ($paths as $path) {
            if (Elixir::verbose()) {
                Elixir::console()->line(sprintf(' - Deleting %s...', str_replace(base_path(), '', $path)));
            }

            if (!Elixir::dryRun()) {
                if (is_dir($path)) {
                    rmdir($path);
                } else {
                    unlink($path);
                }
            }
        }

        return true;
    }
}
