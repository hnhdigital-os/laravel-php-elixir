<?php

namespace Bluora\PhpElixir;

use Illuminate\Console\Command;

class ElixirConsoleCommand extends Command
{
    use SharedTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'elixir';

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'elixir {--config=} {--ignore=}';

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
        static::console()->line('__________.__          ___________.__  .__       .__        ');
        static::console()->line("\______   \  |__ ______\_   _____/|  | |__|__  __|__|______ ");
        static::console()->line(' |     ___/  |  \\\\____ \\|    __)_ |  | |  \\  \\/  /  \\_  __ \\');
        static::console()->line(" |    |   |   Y  \  |_> >        \|  |_|  |>    <|  ||  | \/");
        static::console()->line(" |____|   |___|  /   __/_______  /|____/__/__/\_ \__||__|   ");
        static::console()->line("               \/|__|          \/               \/          ");
        static::console()->line('');

        // Check and verify the config file and system setup.
        if (!$this->verify()) {
            return 1;
        }

        $ignore_list = [];
        if ($this->option('ignore')) {
            $ignore_list = explode(',', str_replace(' ', '', $this->option('ignore')));
        }

        // Run each task.
        foreach ($this->tasks as $task_detail) {
            $task = array_get($task_detail, 'task', false);

            if ($task !== false && in_array($task, $ignore_list)) {
                continue;
            }

            $task_class = $task_detail['class'];
            $arguments = $task_detail['arguments'];
            (new $task_class())->run(...$arguments);
        }

        static::commandInfo('Done.');

        return 0;
    }
}
