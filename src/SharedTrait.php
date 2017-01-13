<?php

namespace Bluora\PhpElixir;

use Config;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

trait SharedTrait
{
    /**
     * Static reference to this running command.
     *
     * @var ElixirConsoleCommand
     */
    protected static $console;

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
     * Configuration file name.
     *
     * @var string
     */
    protected $config_yaml_file = '.elixir.yml';

    /**
     * Configuration file's absolute path.
     *
     * @var string
     */
    protected $config_yaml_file_path = '';

    public function __construct()
    {
        parent::__construct();
        static::$console = $this;
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
     * @return bool
     */
    private function verify()
    {
        if (env('APP_ENV') == 'PRODUCTION') {
            $this->error('This script does not run in production.');

            return false;
        }

        if (!empty($this->option('config'))) {
            $this->config_yaml_file = $this->option('config');
        }

        $this->config_yaml_file_path = $this->config_yaml_file;

        if (!file_exists($this->config_yaml_file_path)) {
            $this->config_yaml_file_path = base_path().'/'.$this->config_yaml_file;
        }

        // Check YAML config file exists.
        if (!file_exists($this->config_yaml_file_path) && $this->config_yaml_file == '.elixir.yml') {
            $this->error('Required .elixir.yml file is missing.');
            $this->info('We have copied the example file for you.');
            $this->line('Please edit and re-run this command.');
            copy(__DIR__.'/.elixir.yml.example', base_path().'/'.'.elixir.yml');

            return false;
        } elseif (!file_exists($this->config_yaml_file_path)) {
            $this->error('The config file provided does not exist.');

            return false;
        }

        // Parse the YAML config file.
        try {
            $config = Yaml::parse(file_get_contents($this->config_yaml_file_path));
        } catch (ParseException $e) {
            $this->error(sprintf('Unable to parse .elixir.yml: %s', $e->getMessage()));

            return false;
        }

        static::commandInfo('Config file being used: %s.', $this->config_yaml_file_path);

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

        $available_tasks = [];

        // The config file declares modules that are available.
        if (isset($config['modules'])) {
            foreach ($config['modules'] as $key => $value) {
                if (array_has($value, 0) && class_exists(array_get($value, 0))) {
                    $available_tasks[$key] = array_get($value, 0);
                }
            }
        }

        unset($config['modules']);

        $run_tasks = [];
        $ignore_tasks = [];

        if ($this->name == 'elixir') {
            $ignore_tasks = ['watch'];
        } elseif ($this->name == 'elixir:watch') {
            $run_tasks = ['watch'];
        }

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

            if ((count($ignore_tasks) && in_array($task, $ignore_tasks))
                || (count($run_tasks) && !in_array($task, $run_tasks))) {
                continue;
            }

            if (!isset($available_tasks[$task])) {
                $this->error(sprintf('\'%s\' has been declared as a class but is missing from the modules list.', $task));
                exit();
                continue;
            }

            // Create the class name and check.
            $task_class = $available_tasks[$task];

            foreach ($entries as $key => $settings) {

                $key = $this->parseConstants($key);
                $settings = $this->parseConstants($settings);

                if (!$task_class::verify($key, $settings)) {
                    return false;
                }
                $this->tasks[] = ['class' => $task_class, 'arguments' => [$key, $settings], 'task' => $task];
            }
        }

        return true;
    }

    /**
     * Parse paths that exist in values.
     *
     * @param mixed $value
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
     * @return bool
     */
    public static function dryRun()
    {
        return isset(static::console()->options['dry-run']) && static::console()->options['dry-run'] == true;
    }

    /**
     * Verbose is on.
     *
     * @return bool
     */
    public static function verbose()
    {
        return isset(static::console()->options['verbose']) && static::console()->options['verbose'] == true;
    }

    /**
     * Store paths by allow check path confirmation.
     *
     * @return bool
     */
    public static function storePath($path)
    {
        $paths = func_get_args();
        if (count($paths) >= 2) {
            foreach ($paths as $path) {
                self::storePath($path);
            }
        } elseif (!empty($path)) {
            static::console()->path_check[$path] = true;
        }
    }

    /**
     * Check and return path.
     *
     * @param string $original_path
     * @param bool   $create_folder
     *
     * @return string|bool
     */
    public static function checkPath($original_path, $create_folder = false, $only_check = false)
    {
        list($path, $options) = self::parseOptions(trim($original_path));
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
            self::makeDir($path, true);

            return $path;
        }
        static::console()->error(sprintf('Path %s can not be found.', $original_path));

        $trace = debug_backtrace()[0];
        if (array_has($trace, 'file')) {
            static::console()->line(array_get($trace, 'file').':'.array_get($trace, 'line'));
        }

        return false;
    }

    /**
     * Parse options off a string.
     *
     * @return array
     */
    public static function parseOptions($input)
    {
        $input_array = explode('?', $input);
        $string = $input_array[0];
        $string_options = !empty($input_array[1]) ? $input_array[1] : '';
        $options = [];
        parse_str($string_options, $options);

        return [$string, $options];
    }

    /**
     * Scan recursively through each folder for all files and folders.
     *
     * @param string $scan_path
     * @param bool   $include_folders
     * @param bool   $include_files
     * @param int    $depth
     *
     * @return void
     */
    public static function scan($scan_path, $include_folders = true, $include_files = true, $depth = -1)
    {
        $paths = [];

        if (substr($scan_path, -1) != '/') {
            $scan_path .= '/';
        }

        try {
            $contents = scandir($scan_path);

            foreach ($contents as $key => $value) {
                if ($value === '.' || $value === '..') {
                    continue;
                }
                $absolute_path = $scan_path.$value;
                if (is_dir($absolute_path) && $depth != 0) {
                    $new_paths = self::scan($absolute_path.'/', $include_folders, $include_files, $depth - 1);
                    $paths = array_merge($paths, $new_paths);
                }
                if ((is_file($absolute_path) && $include_files) || (is_dir($absolute_path) && $include_folders)) {
                    $paths[] = $absolute_path;
                }
            }
        } catch (\Exception $exception) {
            static::console()->error(sprintf('Path %s %s.', $scan_path, $exception->getMessage()));
            exit(1);
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
            $paths = array_filter($paths, function ($path) use ($filter) {
                $module = pathinfo($path, PATHINFO_EXTENSION);

                return in_array($module, $filter);
            });
        }

        return $paths;
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
        static::console()->info(sprintf($template, ...$arguments));
    }

    /**
     * Make directories using the provided path.
     *
     * @param string $path
     * @param bool   $is_directory
     *
     * @return void
     */
    public static function makeDir($path, $is_directory = false)
    {
        $path = $is_directory ? $path : dirname($path);
        if (!file_exists($path)) {
            $parent_path = $path;
            while (!file_exists($parent_path)) {
                $parent_path = dirname($parent_path);
            }

            mkdir($path, fileperms($parent_path), true);
        }
    }
}
