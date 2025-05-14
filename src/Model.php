<?php

namespace Absorbing\CsvModel;

use Illuminate\Support\Collection;

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

    /**
     * Stores query constraints for filtering data.
     * Used to build complex queries with multiple conditions.
     *
     * @var array<callable> Array of filter callbacks
     */
    protected array $queryConstraints = [];

    /**
     * The delimiter character used to separate fields in the CSV file.
     * Defaults to comma (,).
     *
     * @var string
     */
    protected string $delimiter = ',';

    /**
     * The enclosure character used to wrap field values in the CSV file.
     * Defaults to double quote (").
     *
     * @var string
     */
    protected string $enclosure = '"';

    /**
     * The escape character used in the CSV file.
     * Defaults to backslash (\).
     *
     * @var string
     */
    protected string $escape = '\\';

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

        $this->headers = $this->headers ?: fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape);

        while(($line = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape)) !== false) {
            $row = array_combine($this->headers, $line);

            if($this->primaryKey && !isset($row[$this->primaryKey])) {
                throw new \RuntimeException("Primary key '{$this->primaryKey}' not found in CSV row");
            }

            $this->rows[] = $this->castRow($row);
        }

        fclose($handle);
    }

    /**
     * Casts row values according to the defined casting rules in $cast property.
     *
     * @param array<string,mixed> $row Associative array representing a CSV row
     * @return array<string,mixed> The row with values cast to their specified types
     */
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

    public function all(): Collection
    {
        return collect($this->rows);
    }

    /**
     * Searches for a record in the rows based on the provided value and primary key.
     *
     * @param string $value The value to search for in the rows using the primary key.
     * @return array|null Returns the matching row as an associative array if found, or null if no match is found.
     */
    public function find(string $value): ?array
    {
        if(!$this->primaryKey) {
            throw new \RuntimeException('Cannot call find() without setting $primaryKey');
        }

        foreach($this->rows as $row) {
            if(($row[$this->primaryKey] ?? null) == $value) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Retrieves filtered rows based on applied constraints.
     *
     * @return \Illuminate\Support\Collection Collection of filtered rows
     */
    public function get(): Collection
    {
        $filtered = $this->rows;

        foreach($this->queryConstraints as $constraint) {
            $filtered = array_filter($filtered, $constraint);
        }

        $this->queryConstraints = [];
        return collect(array_values($filtered));
    }

    /**
     * Adds a where clause to filter rows based on column value.
     *
     * @param string $column The column name to filter on
     * @param string $value The value to match against
     * @return static Returns the current instance for method chaining
     */
    public function where(string $column, string $value): static
    {
        $this->queryConstraints[] = fn($row) => ($row[$column] ?? null) == $value;
        return $this;
    }
}