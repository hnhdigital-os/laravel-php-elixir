<?php

namespace Bluora\PhpElixir\Modules;

use Bluora\PhpElixir\AbstractModule;
use Bluora\PhpElixir\ElixirConsoleCommand as Elixir;

class CombineModule extends AbstractModule
{
    /**
     * Verify the configuration for this task.
     *
     * @param string $destination_path
     * @param array  $source_paths
     *
     * @return bool
     */
    public static function verify($destination_path, $source_paths)
    {
        foreach ($source_paths as $path) {
            // Remove query string if appended.
            list($path, $path_options) = Elixir::parseOptions($path);

            // Remove search depth if appended.
            if (substr($path, -2) == '**') {
                $path = substr($path, 0, -2);
            } elseif (substr($path, -1) == '*' || substr($path, -1) == '/') {
                $path = substr($path, 0, -1);
            }

            // Check that this path exists.
            if (!Elixir::checkPath($path, false, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Run the task.
     *
     * @param string $destination_path
     * @param array  $source_paths
     *
     * @return bool
     */
    public function run($destination_path, $source_paths)
    {
        Elixir::commandInfo('Executing \'combine\' module...');
        Elixir::console()->line('');
        Elixir::console()->info('   Combining Files...');
        foreach ($source_paths as $path) {
            Elixir::console()->line(sprintf(' - %s', $path));
        }
        Elixir::console()->line('');
        Elixir::console()->info('   Saving To...');
        Elixir::console()->line(sprintf(' - %s', $destination_path));
        Elixir::console()->line('');

        return $this->process($destination_path, $source_paths);
    }

    /**
     * Process the task.
     *
     * @param string $destination_path
     * @param array  $source_paths
     *
     * @return bool
     */
    private function process($destination_path, $source_paths)
    {
        $contents = '';

        // Load the contents of each file.
        foreach ($source_paths as $path) {
            // Remove query string if appended.
            list($path, $path_options) = Elixir::parseOptions($path);

            // Remove search depth if appended.
            if (substr($path, -2) == '**') {
                $path = substr($path, 0, -2);
                $method = 'all';
            } elseif (substr($path, -1) == '*' || substr($path, -1) == '/') {
                $path = substr($path, 0, -1);
                $method = 'base';
            } else {
                $file_paths = [$path];
            }

            // Lookup file paths
            if (!isset($file_paths)) {
                $method_arguments = ($method == 'base') ? [true, 1] : [];
                $file_paths = Elixir::scan($path, false, ...$method_arguments);
                $file_paths = Elixir::filterPaths($file_paths, array_get($path_options, 'filter', ''));
            }

            foreach ($file_paths as $file_path) {
                if (file_exists($file_path)) {
                    if (Elixir::verbose()) {
                        Elixir::console()->line(sprintf('   Adding:   %s', str_replace(base_path(), '', $file_path)));
                    }
                    $relative_path = str_replace(base_path(), '', $file_path);
                    $contents .= sprintf("\n/* %s */\n\n", $relative_path);
                    $contents .= file_get_contents($file_path);
                }
            }
        }

        // Put the contents into the new file.
        Elixir::makeDir($destination_path);
        if (Elixir::verbose()) {
            Elixir::console()->line(sprintf('   Created:   %s', str_replace(base_path(), '', $destination_path)));
            Elixir::console()->line('');
        }

        if (!Elixir::dryRun()) {
            file_put_contents($destination_path, $contents);
        }
    }
}
