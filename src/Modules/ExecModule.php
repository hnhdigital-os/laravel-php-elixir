<?php

namespace PhpElixir\Modules;

use PhpElixir\AbstractModule;
use PhpElixir\ElixirConsoleCommand as Elixir;

class ExecModule extends AbstractModule
{
    /**
     * Verify the configuration for this task.
     *
     * @param string $executable_file_path
     * @param string $arguments
     *
     * @return bool
     */
    public static function verify($executable_file_path, $arguments)
    {
        // We can't execute a folder.
        if (is_dir($executable_file_path)) {
            Elixir::console()->error('Provided path is a folder.');

            return false;
        }

        $original_executable_file_path = $executable_file_path;

        if (($executable_file_path = self::getAbsolutePath($executable_file_path)) === false) {
            Elixir::console()->error(sprintf('Can not find executable %s.', $original_executable_file_path, $executable_file_path));

            return false;
        }

        // Check permissions to execute this file
        $permissions = fileperms($executable_file_path);
        $file_stat = stat($executable_file_path);
        $file_uid = posix_getuid();
        $file_gid = posix_getgid();

        $is_executable = false;

        if ($file_stat['uid'] == $file_uid) {
            $is_executable = (($permissions & 0x0040) ?
                (($permissions & 0x0800) ? true : true) :
                (($permissions & 0x0800) ? true : $is_executable));
        }

        if ($file_stat['gid'] == $file_gid) {
            $is_executable = (($permissions & 0x0008) ?
                (($permissions & 0x0400) ? true : true) :
                (($permissions & 0x0400) ? true : $is_executable));
        }

        $is_executable = (($permissions & 0x0001) ?
            (($permissions & 0x0200) ? true : true) :
            (($permissions & 0x0200) ? true : $is_executable));

        if (!$is_executable) {
            Elixir::console()->error(sprintf('Can not run %s %s', $original_executable_file_path, $arguments));
        }

        return $is_executable;
    }

    /**
     * Run the task.
     *
     * @param string $executable_file_path
     * @param string $arguments
     *
     * @return bool
     */
    public function run($executable_file_path, $arguments)
    {
        Elixir::commandInfo('Executing \'exec\' module...');
        Elixir::console()->line('');
        Elixir::console()->info('   Executing...');
        Elixir::console()->line(sprintf(' - %s %s', $executable_file_path, $arguments));
        Elixir::console()->line('');

        $executable_file_path = self::getAbsolutePath($executable_file_path);

        // Run the command, show output if verbose is on.
        if (!Elixir::dryRun()) {
            $run_function = (Elixir::verbose()) ? 'passthru' : 'exec';
            $arguments .= (!Elixir::verbose()) ? ' > /dev/null 2> /dev/null' : '';

            $run_function(sprintf('%s %s', $executable_file_path, $arguments));

            if (Elixir::verbose()) {
                Elixir::console()->line('');
            }
        }

        return true;
    }

    /**
     * Get absolute path for a executable file.
     *
     * @param string $path
     *
     * @return string|bool
     */
    private static function getAbsolutePath($path)
    {
        if (file_exists($path)) {
            return $path;
        }
        $path = trim(shell_exec(sprintf('which %s 2>&1', $path)));

        if (stripos($path, 'which: no') === false) {
            $path = trim(shell_exec(sprintf('readlink -f %s', $path)));
        } else {
            return false;
        }

        return file_exists($path) ? $path : false;
    }
}
