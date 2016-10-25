<?php

namespace PhpElixir;

use Illuminate\Console\Command;

class ElixirConsoleCommand extends Command
{
    use SharedTrait;

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
        if (!$this->verify()) {
            return 1;
        }

        static::commandInfo('Using yaml file %s.', base_path().'/'.$this->yml_options);

        foreach ($this->tasks as $task_detail) {
            $task_class = $task_detail['class'];
            $arguments = $task_detail['arguments'];
            (new $task_class())->run(...$arguments);
        }

        static::commandInfo('Done.');

        return 0;
    }
}
