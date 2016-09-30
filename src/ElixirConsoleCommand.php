<?php

namespace PhpElixir;

use Config;
use Illuminate\Console\Command;

class ElixirConsoleCommand extends Command
{
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
        if (!file_exists(base_path('.elixir.yml'))) {
            $this->error(sprintf('.elixir file is missing.', $model));

            return 1;
        }
    }
}