<?php

namespace PhpElixir;

use Config;
use Illuminate\Console\Command;

class ElixirWatchCommand extends Command
{
    use SharedTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'elixir:watch';

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'elixir:watch {--config=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the elixir watch runner.';

    /**
     * Notify instance.
     *
     * @var array
     */
    private $watcher = [];

    /**
     * Constants for what we need to be notified about.
     *
     * @var array
     */
    private $watch_constants = IN_CLOSE_WRITE | IN_MOVE | IN_CREATE | IN_DELETE;

    /**
     * Track notification watch to path.
     *
     * @var array
     */
    private $track_watches = [];

    /**
     * Options for paths.
     *
     * @var array
     */
    private $path_options = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Check and verify the config file and system setup.
        if (!$this->verify()) {
            return 1;
        }

        if (!function_exists('inotify_init')) {
            static::console()->error('You need to install PECL inotify to be able to use elixir:watch.');

            return 1;
        }

        // Initialize an inotify instance.
        $this->watcher = inotify_init();

        // Add a watch for each listed path
        foreach ($this->tasks as $task_detail) {
            $this->addPath($task_detail['arguments'][1]);
        }

        static::console()->line('');

        return $this->listen();
    }

    /**
     * Listen for notification.
     *
     * @return int
     */
    private function listen()
    {
        // As long as we have watches that exist, we keep looping.
        while (count($this->track_watches)) {
            // Check the inotify instance for any change events.
            $events = inotify_read($this->watcher);

            // One or many events occured.
            if ($events !== false && count($events)) {
                // Changes occurred that require elixir to run.
                if ($this->processEvents($events)) {
                    $bar = $this->output->createProgressBar();
                    $bar->setFormat('   [%bar%] %elapsed:6s%');
                    $op = [];
                    exec('nohup php artisan elixir > /dev/null 2>&1 & echo $!', $op);
                    $pid = (int) $op[0];
                    $running = true;
                    while ($running) {
                        $bar->advance();
                        $op = [];
                        exec('ps -p '.$pid, $op);
                        $running = isset($op[1]);
                    }
                    $bar->finish();
                    static::console()->line('');
                    static::console()->line('');
                }
            }
        }

        return 0;
    }

    /**
     * Process the events that have occured.
     *
     * @param array $events
     *
     * @return bool
     */
    private function processEvents($events)
    {
        foreach ($events as $event_detail) {
            $is_dir = false;

            // Directory events have a different hex, convert to the same number for a file event.
            $hex = (string) dechex($event_detail['mask']);
            if (substr($hex, 0, 1) == '4') {
                $hex[0] = '0';
                $event_detail['mask'] = hexdec((int) $hex);
                $is_dir = true;
            }

            // This event is ignored, obviously.
            if ($event_detail['mask'] == IN_IGNORED) {
                $this->removePath($event_detail['wd']);
            }

            // This event refers to a path that exists.
            elseif (isset($this->track_watches[$event_detail['wd']])) {
                // File or folder path
                $file_path = $this->track_watches[$event_detail['wd']].'/'.$event_detail['name'];
                $path_options = $this->path_options[$event_detail['wd']];

                if ($is_dir) {
                    switch ($event_detail['mask']) {
                        // New folder created.
                        case IN_CREATE:
                        // New folder was moved, so need to watch new folders.
                        // New files will run elixir.
                        case IN_MOVED_TO:
                            $this->addPath($file_path, $path_options);
                            break;

                        // Folder was deleted or moved.
                        // Each file will trigger and event and so will run elixir then.
                        case IN_DELETE:
                        case IN_MOVED_FROM:
                            $this->removePath($file_path);
                            break;
                    }

                    return false;
                }

                // Check file extension against the specified filter.
                $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
                if (isset($path_options['filter']) && $file_extension != '') {
                    if (count($path_options['filter_allowed']) && !in_array($file_extension, $path_options['filter_allowed'])) {
                        return false;
                    }
                    if (count($path_options['filter_not_allowed']) && in_array('!'.$file_extension, $path_options['filter_not_allowed'])) {
                        return false;
                    }
                }

                // Run elixir for all these file events.
                switch ($event_detail['mask']) {
                    case IN_CLOSE_WRITE:
                    case IN_MOVED_TO:
                    case IN_MOVED_FROM:
                    case IN_DELETE:
                        return true;
                        break;
                }
            }
        }

        return false;
    }

    /**
     * Add a path to watch.
     *
     * @param string $path
     *
     * @return void
     */
    private function addPath($original_path, $options = false)
    {
        static::console()->line('   Watching '.$original_path);
        $path = trim($original_path);

        if ($options === false) {
            list($path, $options) = self::parseOptions($path);
        }

        if (isset($options['filter'])) {
            $options['filter'] = explode(',', $options['filter']);
            $options['filter_allowed'] = array_filter($options['filter'], function ($value) {
                return substr($value, 0, 1) !== '!';
            });
            $options['filter_not_allowed'] = array_filter($options['filter'], function ($value) {
                return substr($value, 0, 1) === '!';
            });
        }

        // Watch this folder.
        $watch_id = inotify_add_watch($this->watcher, $path, $this->watch_constants);
        $this->track_watches[$watch_id] = $path;
        $this->path_options[$watch_id] = $options;

        if (is_dir($path)) {
            // Find and watch any children folders.
            $folders = $this->scan($path, true, false);
            foreach ($folders as $folder_path) {
                if (file_exists($folder_path)) {
                    $watch_id = inotify_add_watch($this->watcher, $folder_path, $this->watch_constants);
                    $this->track_watches[$watch_id] = $folder_path;
                    $this->path_options[$watch_id] = $options;
                }
            }
        }
    }

    /**
     * Remove path from watching.
     *
     * @param string $file_path
     *
     * @return void
     */
    private function removePath($file_path)
    {
        // Find the watch ID for this path.
        $watch_id = array_search($file_path, $this->track_watches);

        // Remove the watch for this folder and remove from our tracking array.
        if ($watch_id !== false && isset($this->track_watches[$watch_id])) {
            static::console()->line('   Removing watch for '.$this->track_watches[$watch_id]);
            try {
                inotify_rm_watch($this->watcher, $watch_id);
            } catch (\Exception $exception) {
            }
            unset($this->track_watches[$watch_id]);
            unset($this->path_options[$watch_id]);
        }
    }
}
