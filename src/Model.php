<?php

namespace Absorbing\CsvModel;

use Illuminate\Support\Collection;
use Illuminate\Support;

abstract class Model
{
    /**
     * The primary key column name used to uniquely identify records in the CSV file.
     * Defaults to 'id' if not specified in the configuration.
     *
     * @var string|null
     */
    protected ?string $primaryKey;

    /**
     * Array of column headers from the CSV file.
     * Each element represents a column name in the order they appear.
     *
     * @var array<int,string>
     */
    protected array $headers = [];

    /**
     * The absolute or relative path to the CSV file that this model represents.
     * This path is used to locate and read the CSV file during model initialization.
     *
     * @var string
     */
    protected string $path;

    /**
     * Defines type casting rules for CSV columns.
     * Associates column names with their desired data types for automatic type conversion.
     *
     * Supported types:
     * - 'int': Cast to integer
     * - 'float': Cast to floating point number
     * - 'bool': Cast to boolean
     * - 'string': Cast to string
     *
     * Example:
     * ['age' => 'int', 'price' => 'float', 'active' => 'bool']
     *
     * @var array<string,string> Key-value pairs where key is column name and value is target type
     */
    protected array $cast = [];

    /**
     * Stores the data rows read from the CSV file as an array of associative arrays.
     * Each element represents one row from the CSV file where keys are column names
     * from headers and values are the corresponding cell values.
     *
     * @var array<int,array<string,mixed>> Array of rows where each row is an associative array
     */
    protected array $rows = [];

    public function __construct()
    {
        $this->loadCsv();
    }

    /**
     * Loads data from a CSV file specified in the configuration and populates the headers and rows properties.
     *
     * The method reads the CSV file using the provided configuration options such as path, delimiter, enclosure, and escape character.
     * It parses the file, extracting headers and rows, and stores them appropriately.
     * If a CSV file cannot be opened, an exception is thrown.
     *
     * @return void
     * @throws \RuntimeException
     */
    protected function loadCsv(): void
    {
        if(!isset($this->path)) {
            throw new \RuntimeException('CSV file path not specified');
        }

        $handle = fopen(storage_path($this->path), 'r');

        if(!$handle) {
            throw new \RuntimeException("Cannot open CSV file at {$this->path}");
        }


    }

    protected function castRow(array $row): array
    {
        foreach ($this->cast as $key => $type) {
            if (isset($row[$key])) {
                $row[$key] = match ($type) {
                    'int' => (int) $row[$key],
                    'float' => (float) $row[$key],
                    'bool' => (bool) $row[$key],
                    'string' => (string) $row[$key],
                    default => $row[$key],
                };
            }
        }
        return $row;
    }

    public function all(): array
    {
        return $this->rows;
    }

    /**
     * Searches for a record in the rows based on the provided value and primary key.
     *
     * @param string $value The value to search for in the rows using the primary key.
     * @return array|null Returns the matching row as an associative array if found, or null if no match is found.
     */
    public function find(string $value): ?array
    {
        foreach($this->rows as $row) {
            if(($row[$this->primaryKey] ?? null) == $value) {
                return $row;
            }
        }

        return null;
    }

    public function where(string $column, string $value): array
    {
        return array_filter(
            $this->rows,
            fn($row) => ($row[$column] ?? null) == $value
        );
    }
}