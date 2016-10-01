<?php

namespace PhpElixir\Extensions;

use PhpElixir\AbstractExtension;
use PhpElixir\ElixirConsoleCommand as Elixir;

class ReplaceExtension extends AbstractExtension
{
    /**
     * Verify the configuration for this task.
     *
     * @param  string $source_path
     * @param  array  $find_replace
     *
     * @return boolean
     */
    public static function verify($source_path, $find_replace)
    {
        if (!Elixir::checkPath($source_path, false, true)) {
            return false;
        }

        Elixir::storePath($source_path);

        return true;
    }

    /**
     * Run the task.
     *
     * @param  string $source_path
     * @param  array  $find_replace
     *
     * @return boolean
     */
    public function run($source_path, $find_replace)
    {
        Elixir::commandInfo('Executing \'replace\' extension...');
        Elixir::console()->line('');
        Elixir::console()->info('   Updating...');
        Elixir::console()->line(sprintf(' - %s', $source_path));
        Elixir::console()->line('');
        Elixir::console()->info('   Find this string...');
        Elixir::console()->line(sprintf(' - %s', $find_replace[0]));
        Elixir::console()->line('');
        Elixir::console()->info('   and replace it with...');
        Elixir::console()->line(sprintf(' - %s', $find_replace[1]));
        Elixir::console()->line('');

        return $this->process($source_path, $find_replace);
    }

    /**
     * Process the task.
     *
     * @param  string $source_path
     * @param  array  $find_replace
     *
     * @return boolean
     */
    private function process($source_path, $find_replace)
    {
        if (!isset($find_replace[1])) {
            $find_replace[1] = '';
        }

        if (!isset($find_replace[2])) {
            $find_replace[2] = '';
        }

        $find = trim($find_replace[0]);
        $replace = trim($find_replace[1]);
        $text_options = trim($find_replace[2]);

        $options = [];
        parse_str($text_options, $options);

        list($source_path, $path_options) = Elixir::parseOptions($source_path);

        $options = array_merge_recursive($options, $path_options);

        // Single file is provided.
        if (is_file($source_path)) {
            return $this->findReplace($find, $replace, $source_path, $options);
        } else {
            $paths = Elixir::scan($source_path, false);            
            $paths = Elixir::filterPaths($paths, array_get($options, 'filter', ''));

            foreach ($paths as $source_path) {
                Elixir::console()->line(sprintf('   Processing %s', $source_path));
                $this->findReplace($find, $replace, $source_path, $options);
                Elixir::console()->line('');
            }
        }
        return true;
    }

    /**
     * Find and replace in a file.
     *
     * @return true;
     */
    private function findReplace($original_find, $replace, $source_path, $options)
    {
        if (file_exists($source_path)) {
            $find = $original_find;
            if (!isset($original_find_array[1])) {
                $original_find_array[1] = '';
            }

            // Enabled PCRE string replacement.
            if (array_has($options, 'preg')) {
                $find = '~'.$find.'~';
                $method = 'preg_replace';
            } else {
                $method = 'str_replace';
            }

            // Get file contents.
            $content = file_get_contents($source_path);

            if (!Elixir::dryRun()) {
                if (Elixir::verbose()) {
                    Elixir::console()->line(sprintf('   Replacing %s with %s using %s', $original_find, $replace, $method));
                }
                // Find and replace.
                $content = $method($original_find, $replace, $content);
                // Update file.
                file_put_contents($source_path, $content);
            }

            // When doing a dry-run with verbose - we use PCRE to do a string count on what would be replaced.
            elseif (Elixir::verbose()) {

                $matches = [];
                if (array_has($options, 'preg')) {
                    $find = preg_quote($find);
                }
                preg_match_all('~'.$find.'~', $content, $matches);
                if (isset($matches[0])) {
                    Elixir::console()->info(sprintf('   Found %s matches for %s.', count($matches[0]), $original_find));
                } else {                    
                    Elixir::console()->line(sprintf('   Found no matches for %s', $original_find));
                }
            }
            return true;
        }
        return false;
    }
}
