<?php

namespace PhpElixir;

use Config;
use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class ElixirConsoleCommand extends Command
{
    /**
     * Static reference to this running command.
     *
     * @var ElixirConsoleCommand
     */
    private static $console;

    /**
     * Parsed options.
     *
     * @var array
     */
    private $options = [];

    /**
     * Parsed paths.
     *
     * @var array
     */
    private $paths = [];

    /**
     * Collection of paths that this tool creates or uses.
     *
     * @var array
     */
    private $path_check = [];

    /**
     * Parsed tasks.
     *
     * @var array
     */
    private $tasks = [];

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'elixir';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the elixir task runner.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        static::$console = $this;

        if (!$this->verify()) {
            return 1;
        }

        static::commandInfo('Using yaml file %s.', base_path('elixir.yml'));

        foreach ($this->tasks as $task_detail) {
            $task_class = $task_detail['class'];
            $arguments = $task_detail['arguments'];
            (new $task_class)->run(...$arguments);
        }

        static::commandInfo('Done.');

        return 0;
    }

    /**
     * Command information.
     *
     * @return void
     */
    public static function commandInfo()
    {
        $arguments = func_get_args();
        $template = array_shift($arguments);
        $template = '[%s] '.$template;
        array_unshift($arguments, date('H:i:s'));
        static::console()->line(sprintf($template, ...$arguments));
    }

    /**
     * Static link to this running command.
     *
     * @return ElixirConsoleCommand
     */
    public static function console()
    {
        return static::$console;
    }

    /**
     * Verify and setup configuration so we can run this command.
     *
     * @return boolean
     */
    private function verify()
    {
        if (env('APP_ENV') == 'PRODUCTION') {
            $this->error('This script does not run in production.');

            return false;
        }

        // Check YAML config file exists.
        if (!file_exists(base_path('elixir.yml'))) {
            $this->error('Required elixir.yml file is missing.');

            return false;
        }

        // Parse the YAML config file.
        try {
            $config = Yaml::parse(file_get_contents(base_path('elixir.yml')));
        } catch (ParseException $e) {
            $this->error(sprintf('Unable to parse elixir.yml: %s', $e->getMessage()));

            return false;
        }

        // The config file has options set.
        if (isset($config['options'])) {
            $this->options = $config['options'];
        }
        unset($config['options']);

        // The config file declares paths. Parse into absolute paths.
        if (isset($config['paths'])) {
            foreach ($config['paths'] as $name => $original_value) {
                $name = trim($name);
                if (!($value = static::checkPath($original_value, true))) {
                    return false;
                }
                $this->paths[$name] = $value;
            }
        }
        unset($config['paths']);

        // The config file declares 3rd party extensions.
        if (isset($config['extensions'])) {

        }
        unset($config['extensions']);

        // Check each of the tasks and add to our task list.
        foreach ($config as $task => $entries) {
            // Skip this task
            if (substr($task, 0, 1) === '!') {
                continue;
            }

            // More than one task being used, so we prepend with {integer}#task.
            if (stripos($task, '#') !== false) {
                $task_array = explode('#', $task);
                $task = $task_array[1];
            }

            // Create the class name and check.
            $task_class = __NAMESPACE__ . '\\Extensions\\' . studly_case($task).'Extension';
            if (!class_exists($task_class)) {
                $this->error(sprintf('Extension \'%s\' can not be found.', $task));

                return false;
            }

            foreach ($entries as $key => $settings) {
                $key = $this->parseConstants($key);
                $settings = $this->parseConstants($settings);
                if (!$task_class::verify($key, $settings)) {
                    return false;
                }
                $this->tasks[] = ['class' => $task_class, 'arguments' => [$key, $settings]];
            }
        }
        return true;
    }

    /**
     * Parse paths that exist in values.
     *
     * @param  mixed $value
     *
     * @return mixed
     */
    private function parseConstants($value)
    {
        if (is_array($value)) {
            foreach ($value as &$sub_value) {
                $sub_value = $this->parseConstants($sub_value);
            }
        } else {
            $value = str_replace(array_keys($this->paths), array_values($this->paths), $value);
            $value = str_replace([' + ', '+', '"', "'"], '', $value);
        }
        return $value;
    }

    /**
     * Dry run only.
     *
     * @return boolean
     */
    public static function dryRun()
    {
        return (isset(static::console()->options['dry-run']) && static::console()->options['dry-run'] == true);
    }

    /**
     * Verbose is on.
     *
     * @return boolean
     */
    public static function verbose()
    {
        return (isset(static::console()->options['verbose']) && static::console()->options['verbose'] == true);
    }

    /**
     * Store paths by allow check path confirmation.
     *
     * @return boolean
     */
    public static function storePath($path)
    {
        $paths = func_get_args();
        if (count($paths) >= 2) {
            foreach ($paths as $path) {
                self::storePath($path);
            }            
        } else {
            static::console()->path_check[$path] = true;
        }
    }

    /**
     * Check and return path.
     *
     * @param  string  $original_path
     * @param  boolean $create_folder
     *
     * @return string|boolean
     */
    public static function checkPath($original_path, $create_folder = false, $only_check = false)
    {
        $path = trim($original_path);
        $base_path = str_replace('//', '/', base_path().'/'.$path);

        if ($only_check) {
            if (isset(static::console()->path_check[$path])) {
                return $path;
            } elseif (isset(static::console()->path_check[$base_path])) {
                return $base_path;
            }
        }

        if (file_exists($base_path)) {
            return $base_path;
        } elseif (file_exists($path)) {
            return $path;
        }
        if ($create_folder) {
            $path = base_path().'/'.str_replace(base_path(), '', $path);

            $existing_parent_path = $path;
            while (!file_exists($existing_parent_path)) {
                $existing_parent_path = dirname($existing_parent_path);
            }

            mkdir($path, fileperms($existing_parent_path), true);
            return $path;
        }
        static::console()->error(sprintf('Path %s can not be found.', $original_path));
        return false;
    }

    /**
     * Parse options off a string.
     *
     * @return array
     */
    public static function parseOptions($input)
    {
        $input_array = explode('|', $input);
        $string = $input_array[0];
        $string_options = !empty($input_array[1]) ? $input_array[1] : '';
        $options = [];
        parse_str($string_options, $options);

        return [$string, $options];
    }

    /**
     * Scan recursively through each folder for all files and folders.
     * 
     * @param  string  $scan_path
     * @param  boolean $include_folders
     *
     * @return void
     */
    public static function scan($scan_path, $include_folders = true)
    {
        $paths = [];

        if (substr($scan_path, -1) != '/') {
            $scan_path .= '/';
        }

        $contents = scandir($scan_path);

        foreach ($contents as $key => $value) {
            if ($value === '.' || $value === '..') {
                continue;
            }
            $absolute_path = $scan_path.$value;
            if (is_dir($absolute_path)) {
                $new_paths = self::scan($absolute_path.'/', $include_folders);
                $paths = array_merge($paths, $new_paths);
            }
            if (is_file($absolute_path) || (is_dir($absolute_path) && $include_folders)) {
                $paths[] = $absolute_path;
            }
        }

        return $paths;
    }

    /**
     * Filter array of paths.
     *
     * @return array
     */
    public static function filterPaths($paths, $filter)
    {
        if (!empty($filter)) {
            $filter = explode(',', $filter);
        }

        if (is_array($filter) && count($filter)) {
            $paths = array_filter($paths, function($path) use ($filter) {
                $extension = pathinfo($path, PATHINFO_EXTENSION);
                return in_array($extension, $filter);
            });
        }

        return $paths;
    }
}
