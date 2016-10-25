<?php

namespace PhpElixir;

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
    protected $signature = 'elixir {--config=}';

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
        // Check and verify the config file and system setup.
        if (!$this->verify()) {
            return 1;
        }

        // Run each task.
        foreach ($this->tasks as $task_detail) {
            $task_class = $task_detail['class'];
            $arguments = $task_detail['arguments'];
            (new $task_class())->run(...$arguments);
        }

        static::commandInfo('Done.');

        return 0;
    }
}
