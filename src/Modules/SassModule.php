<?php

namespace Bluora\PhpElixir\Modules;

use Bluora\PhpElixir\AbstractModule;
use Bluora\PhpElixir\ElixirConsoleCommand as Elixir;
use Leafo\ScssPhp\Compiler;

class SassModule extends AbstractModule
{
    /**
     * Verify the configuration for this task.
     *
     * @param string $source_path
     * @param mixed  $destination_path
     *
     * @return bool
     */
    public static function verify($source_path, $destination_path)
    {
        if (!Elixir::checkPath($source_path, false, true)) {
            return false;
        }

        Elixir::storePath($source_path, $destination_path);

        if (is_array($destination_path)) {
            return false;
        }

        return true;
    }

    /**
     * Run the task.
     *
     * @param string $source_path
     * @param string $destination_path
     *
     * @return bool
     */
    public function run($source_path, $destination_path)
    {
        Elixir::commandInfo('Executing \'sass\' module...');
        Elixir::console()->line('');
        Elixir::console()->info('   Fetching Sass Source Files...');
        Elixir::console()->line(sprintf(' - %s', $source_path));
        Elixir::console()->line('');
        Elixir::console()->info('   Saving To...');
        Elixir::console()->line(sprintf(' - %s', $destination_path));
        Elixir::console()->line('');

        return $this->process($source_path, $destination_path);
    }

    /**
     * Process the task.
     *
     * @param string $source_path
     * @param string $destination_path
     *
     * @return bool
     */
    private function process($source_path, $destination_path)
    {
        $import_path = dirname($source_path);

        if (!Elixir::dryRun()) {
            $scss = new Compiler();
            $scss->addImportPath($import_path);
            $scss->addImportPath(base_path());
            $compiled_scss = $scss->compile(file_get_contents($source_path));
            Elixir::checkPath(dirname($destination_path), true);
            file_put_contents($destination_path, $compiled_scss);
        }

        return true;
    }
}
