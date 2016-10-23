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
        foreach ($this->tasks as $task_index => $task_detail) {
            $this->addPath($task_detail['arguments'][1]);
        }

        static::console()->line('');

        // As long as we have watches that exist, we keep looping.
        while (count($this->track_watches)) {
            // Check the inotify instance for any change events.
            $events = inotify_read($this->watcher);

            // One or many events occured.
            if ($events !== false && count($events)) {
                $run_elixir = false;
                // Check each event.
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

                        if ($is_dir) {
                            switch ($event_detail['mask']) {
                                // New folder created.
                                case IN_CREATE:
                                // New folder was moved, so need to watch new folders.
                                // New files will run elixir.
                                case IN_MOVED_TO:
                                    $this->addPath($file_path);
                                    break;

                                // Folder was deleted or moved.
                                // Each file will trigger and event and so will run elixir then.
                                case IN_DELETE:
                                case IN_MOVED_FROM:
                                    $this->removePath($file_path);
                                    break;
                            }
                        } else {
                            // Run elixir for all these file events.
                            switch ($event_detail['mask']) {
                                case IN_CLOSE_WRITE:
                                case IN_MOVED_TO:
                                case IN_MOVED_FROM:
                                case IN_DELETE:
                                    $run_elixir = true;
                                    break;
                            }
                        }
                    }
                }

                // Changes occurred that require elixir to run.
                if ($run_elixir) {
                    static::console()->line('   Changes occured. Running elixir.');
                    exec('php artisan elixir');
                    static::console()->line('');
                    $run_elixir = false;
                }
            }
        }

        return 0;
    }

    /**
     * Add a path to watch.
     *
     * @param string $path
     *
     * @return void
     */
    private function addPath($path)
    {
        static::console()->line('   Watching '.$path);

        // Watch this folder.
        $watch_id = inotify_add_watch($this->watcher, $path, $this->watch_constants);
        $this->track_watches[$watch_id] = $path;

        if (is_dir($path)) {
            // Find and watch any children folders.
            $folders = $this->scan($path, true, false);
            foreach ($folders as $folder_path) {
                if (file_exists($folder_path)) {
                    $watch_id = inotify_add_watch($this->watcher, $folder_path, $this->watch_constants);
                    $this->track_watches[$watch_id] = $folder_path;
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
        }
    }
}
