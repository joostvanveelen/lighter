<?php

namespace Lighter;

/**
 * Shell is a wrapper to allow easy execution of commands and capture of the exit status and output.
 *
 * @package Lighter
 */
class Shell
{
    /**
     * @var int
     */
    private $status = 0;

    /**
     * @var array
     */
    private $output = [];

    /**
     * Execute an external program.
     *
     * @param string $command
     *
     * @return int status
     */
    public function exec(string $command): int
    {
        $this->output = [];
        $this->status = 0;
        exec($command . ' 2>&1', $this->output, $this->status);

        return $this->status;
    }

    /**
     * Execute an external program and display it's output.
     *
     * @param string $command
     *
     * @return int status
     */
    public function passthru(string $command): int
    {
        //throw new Exception('Starting a shell is currently work in progress.');
        $this->status = 0;
        $this->output = [];
        system($command, $this->status);

        return $this->status;
    }

    /**
     * Retrieve the exit status of the last run program.
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Retrieve the output of the last run program.
     *
     * @param int $lineNumber The output line to retrieve, or null for the complete output
     *
     * @return string|null
     */
    public function getOutput($lineNumber = null): ?string
    {
        if ($lineNumber === null) {
            return implode(PHP_EOL, $this->output);
        }

        return $this->output[$lineNumber] ?? null;
    }

    /**
     * Retrieve the output of the last run program as an array of lines.
     *
     * @return string[]
     */
    public function getOutputLines(): array
    {
        return $this->output;
    }
}
