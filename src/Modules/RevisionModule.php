<?php

namespace Bluora\PhpElixir\Modules;

use Bluora\PhpElixir\AbstractModule;
use Bluora\PhpElixir\ElixirConsoleCommand as Elixir;

class RevisionModule extends AbstractModule
{
    /**
     * Verify the configuration for this task.
     *
     * @param string $source_path
     * @param array  $options
     *
     * @return bool
     */
    public static function verify($source_path, $options)
    {
        if (!Elixir::checkPath($source_path, false, true)) {
            return false;
        }

        Elixir::storePath($source_path);

        if (empty($options)) {
            return true;
        }

        if (empty($options[0])) {
            return true;
        }

        if (empty($options[1])) {
            return true;
        }

        if (!Elixir::checkPath($options[0])) {
            return false;
        }

        return true;
    }

    /**
     * Run the task.
     *
     * @param string $source_path
     * @param array  $options
     *
     * @return bool
     */
    public function run($source_path, $options)
    {
        Elixir::commandInfo('Executing \'rev\' module...');
        Elixir::console()->line('');
        Elixir::console()->info('   Revisioning files in...');
        Elixir::console()->line(sprintf(' - %s', $source_path));
        Elixir::console()->line('');
        Elixir::console()->info('   Copying them here...');
        Elixir::console()->line(sprintf(' - %s', $options[0]));
        Elixir::console()->line('');
        Elixir::console()->info('   Recording them here...');
        Elixir::console()->line(sprintf(' - %s', $options[1]));
        Elixir::console()->line('');

        if (!isset($options[2])) {
            $options[2] = '';
        }

        return $this->process($source_path, $options[0], $options[1], $options[2]);
    }

    /**
     * Process the task.
     *
     * @param string $source_path
     * @param array  $options
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.NpathComplexity)     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function process($source_path, $desination_folder, $manifest_file, $text_options)
    {
        $paths = Elixir::scan($source_path, false);

        $options = [];
        parse_str($text_options, $options);

        if (!isset($options['hash_length'])) {
            $options['hash_length'] = 8;
        }

        $manifest = [];

        if (Elixir::verbose()) {
            if (isset($options['minify'])) {
                Elixir::console()->line('   Minification is enabled.');
            }
            Elixir::console()->line(sprintf('   Generated hash is set to %s characters.', $options['hash_length']));
            Elixir::console()->line('');
        }

        foreach ($paths as $source_file) {

            // Generate sha1 of file.
            $sha1 = substr(sha1_file($source_file), 0, $options['hash_length']);

            // Source file details
            $path_info = pathinfo($source_file);
            $relative_folder = str_replace($source_path.'/', '', $path_info['dirname']);

            // Minify file if enabled and not already minified (best guess by .min in filename)
            $minify = false;
            $minify_ext = '';
            if (isset($options['minify']) && in_array($path_info['extension'], ['css', 'js'])
                && stripos($source_file, '.min') === false) {
                $minify = true;
                $minify_ext = 'min.';
            }

            // Hashed and minified new file name.
            $destination_file = $desination_folder.'/'.$relative_folder.'/'.$path_info['filename'].'.'.$sha1.'.'.$minify_ext.$path_info['extension'];

            if (!Elixir::dryRun()) {
                // Check that the destination folder exists.
                $new_folder = $desination_folder.'/'.$relative_folder;
                if (!file_exists($new_folder)) {
                    mkdir($new_folder, fileperms($desination_folder), true);
                }

                // Process minification to destination.
                if ($minify) {
                    $class = '\\MatthiasMullie\\Minify\\'.strtoupper($path_info['extension']);
                    (new $class($source_file))->minify($destination_file);
                }

                // Or just copy the file.
                elseif (!$minify) {
                    copy($source_file, $destination_file);
                }
            }

            // Make file paths relative.
            $manifest_source = str_replace($source_path.'/', '', $source_file);
            $manifest_rev = str_replace($desination_folder.'/', '', $destination_file);
            $manifest[$manifest_source] = $manifest_rev;

            if (Elixir::verbose()) {
                Elixir::console()->line(sprintf(' - From: %s', $manifest_rev));
                Elixir::console()->line(sprintf('   To:   %s', $manifest_source));
                Elixir::console()->line('');
            }
        }

        // Make it human viewable.
        if (!Elixir::dryRun()) {
            $json_manifest = json_encode($manifest, JSON_UNESCAPED_SLASHES);
            $json_manifest = preg_replace('~","~', "\",\n\"", $json_manifest);
            $json_manifest = preg_replace('~^\{~', "{\n", $json_manifest);
            $json_manifest = preg_replace('~\}$~', "\n}", $json_manifest);
            $json_manifest = preg_replace('~^\"~m', '  "', $json_manifest);
            $json_manifest = str_replace('":"', '": "', $json_manifest);
            file_put_contents($manifest_file, $json_manifest);

            if (array_has($options, 'php_manifest')) {
                $php_manifest_file = config_path().'/'.basename($manifest_file, '.json').'.php';
                $php_manifest = "<?php\n\n";
                $php_manifest .= "return [\n";
                foreach ($manifest as $asset_path => $build_path) {
                    $php_manifest .= sprintf("    \"%s\" => \"%s\",\n", $asset_path, $build_path);
                }
                $php_manifest .= "];\n";
                file_put_contents($php_manifest_file, $php_manifest);
            }
        }

        return true;
    }
}
