<?php

namespace Lighter\Environment\Type\DockerCompose;

class OutputParser
{
    /**
     * `docker-compose ps` displays in a pseudo-table, using 3 whitespace characters as column separator
     */
    private const PS_SEPARATOR_LENGTH = 3;

    /**
     * Parse the output of 'docker-compose ps' into an array of services
     *
     * @param array $lines
     *
     * @return Service[]
     */
    public function parsePS(array $lines): array
    {
        $columns = $this->getColumns($lines);
        $services = [];

        $currentServiceData = null;
        $header = true;
        foreach ($lines as $lineNumber => $line) {
            if (preg_match('/^-+$/', $line)) {
                $header = false;
                continue;
            }
            if ($header) {
                continue;
            }
            $serviceData = $this->split($line, $columns);
            if (preg_match('/^[A-Z]/', $serviceData[2])) {
                //A new service starts. Finish current service.
                if ($currentServiceData !== null) {
                    $services[] = new Service(...$currentServiceData);
                }
                $currentServiceData = $serviceData;
            } else {
                //Continuation of a service, combine the data with the existing data.
                $currentServiceData = $this->combine($currentServiceData, $serviceData);
            }
        }
        if ($currentServiceData !== null) {
            $services[] = new Service(...$currentServiceData);
        }

        return $services;
    }

    /**
     * Determine the columns in the output.
     *
     * @param string[] $lines
     *
     * @return bool[]
     */
    private function getColumns(array $lines): array
    {
        //determine which text columns has a space for each line
        $whitespaceColumns = [];
        foreach ($lines as $line) {
            //skip header separator line which is only dashes
            if (preg_match('/^-+$/', $line)) {
                continue;
            }
            for ($i = 0, $iMax = strlen($line); $i < $iMax; $i++) {
                $whitespaceColumns[$i] = ($whitespaceColumns[$i] ?? true) && ($line[$i] === ' ');
            }
        }

        //determine each column as start/length
        $columns = [];
        $currentIsWhitespace = true;
        $whitespaceWidth = 0;
        foreach ($whitespaceColumns as $colNum => $isWhitespace) {
            if ($currentIsWhitespace !== $isWhitespace && $isWhitespace === false) {
                if ($whitespaceWidth > 0 && $whitespaceWidth < self::PS_SEPARATOR_LENGTH) {
                    //continue last column
                    $columns[count($columns) - 1]['length'] += $whitespaceWidth;
                } else {
                    //start of a new column
                    $columns[] = [
                        'start'  => $colNum,
                        'length' => 0,
                    ];
                }
            }
            if ($isWhitespace === false) {
                $columns[count($columns) - 1]['length']++;
                $whitespaceWidth = 0;
            } else {
                $whitespaceWidth++;
            }
            $currentIsWhitespace = $isWhitespace;
        }

        return $columns;
    }

    /**
     * @param string[] $text
     * @param array    $columns
     *
     * @return string[]
     */
    private function split($text, $columns): array
    {
        $items = [];
        foreach ($columns as $i => $column) {
            $items[$i] = trim(substr($text, $column['start'], $column['length']));
        }

        return $items;
    }

    /**
     * @param string[] $item1
     * @param string[] $item2
     *
     * @return array
     */
    public function combine(array $item1, array $item2): array
    {
        foreach ($item2 as $i => $value) {
            $item1[$i] .= $value;
        }

        return $item1;
    }
}
