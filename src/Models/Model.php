<?php

namespace FlatModel\CsvModel\Models;

use FlatModel\CsvModel\Traits\HasCoreFeatures;

abstract class Model
{
    use HasCoreFeatures;

    /**
     * The absolute or relative path to the CSV file that this model represents.
     * This path is used to locate and read the CSV file during model initialization.
     *
     * @var string
     */
    protected string $path;

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

    /**
     * Indicates whether the model operates in stream mode.
     *
     * @var bool
     */
    protected bool $stream = false;

    /**
     * Stores the data rows read from the CSV file as an array of associative arrays.
     * Each element represents one row from the CSV file where keys are column names
     * from headers and values are the corresponding cell values.
     *
     * @var array<int,array<string,mixed>> Array of rows where each row is an associative array
     */
    private array $rows = [];

    /**
     * Array of column headers from the CSV file.
     * Each element represents a column name in the order they appear.
     *
     * @var array<int,string>
     */
    protected array $headers = [];

    /**
     * Indicates whether the CSV file has a header row.
     *
     * - true: First row contains column names (default)
     * - false: No header row, all rows are data
     *
     * When false, if $headers is provided, those names will be used for columns.
     * Otherwise, numeric indices (0, 1, 2...) will be used as column keys.
     *
     * @var bool
     */
    protected bool $hasHeaders = true;

    /**
     * Whether to use strict header checking.
     *
     * @var bool
     */
    protected bool $strictHeaders = false;

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
     * Indicates whether the model is writable.
     * If true, the model can be used to write data back to the CSV file.
     * If false, the model is read-only and cannot modify the underlying CSV data.
     *
     * @var bool
     */
    protected bool $writable = false;

    /**
     * Indicates whether the model is append-only
     *
     * @var bool
     */
    protected bool $appendOnly = false;

    /**
     * Enables or disables automatic backups on modification of the file
     *
     * @var bool
     */
    protected bool $enableBackup = false;

    /**
     * Indicates whether the model should flush the data to the CSV file on every modification.
     *
     * @var bool
     */
    protected bool $autoFlush = false;

    /**
     * Returns the complete set of data rows for querying.
     *
     * @return array<int,array<string,mixed>> Array of rows where each row is an associative array
     */
    protected function getRows(): array
    {
        return $this->rows;
    }

    /**
     * Sets the data rows for the model.
     *
     * @param array<int,array<string,mixed>> $rows Array of rows where each row is an associative array
     * @return static Returns the current instance for method chaining
     */
    protected function setRows(array $rows): static
    {
        $this->rows = $rows;
        return $this;
    }

    /**
     * Adds a single row to the model's data.
     * This method appends the provided row to the existing rows array.
     *
     * @param array<string,mixed> $row Associative array representing a single row of data
     * @return static Returns the current instance for method chaining
     */
    protected function setRow(array $row): static
    {
        $this->rows[] = $row;
        return $this;
    }

    /**
     * Returns the delimiter character used to separate fields in the CSV file.
     *
     * @return string The delimiter character.
     */
    protected function getDelimiter(): string
    {
        return $this->delimiter;
    }

    /**
     * Returns the enclosure character used to wrap field values in the CSV file.
     *
     * @return string The enclosure character.
     */
    protected function getEnclosure(): string
    {
        return $this->enclosure;
    }

    /**
     * Returns the escape character used in the CSV file.
     *
     * @return string The escape character.
     */
    protected function getEscape(): string
    {
        return $this->escape;
    }

    /**
     * Resolves a relative or absolute file path.
     *
     * @param string $path Optional path to resolve relative to the model's base path
     * @return string The fully resolved absolute file path
     */
    protected function resolvePath($path = ''): string
    {
        return storage_path($this->path);
    }

    /**
     * Determines whether the model operates in stream mode.
     *
     * Stream mode implies that the model cannot be flushed or written directly.
     *
     * @return bool True if the model is in stream mode, false otherwise
     */
    public function isStream(): bool
    {
        return $this->stream;
    }

    /**
     * Returns the array of headers read from the CSV file.
     *
     * @return array<int,string> Array of column headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Sets the headers for the CSV file.
     * This method is used to define the column names explicitly.
     *
     * @param array<int,string> $headers Array of column headers
     * @return static Returns the current instance for method chaining
     */
    public function setHeaders(array $headers): static
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * Checks if the CSV file has a header row.
     *
     * @return bool
     */
    protected function hasHeaders(): bool
    {
        return $this->hasHeaders;
    }

    /**
     * Checks if strict header validation is enabled.
     *
     * @return bool
     */
    protected function isStrictHeaders(): bool
    {
        return $this->strictHeaders;
    }

    /**
     * Gets the casted types for the model's columns.
     *
     * @return array<string,string> Key-value pairs where key is column name and value is target type
     */
    protected function getCast(): array
    {
        return $this->cast;
    }

    /**
     * Checks if the model is writable
     *
     * @return bool
     */
    protected function isWritable(): bool
    {
        return $this->writable;
    }

    /**
     * Checks if the model is append-only
     *
     * @return bool
     */
    protected function isAppendOnly(): bool
    {
        return $this->appendOnly;
    }

    /**
     * Determines if backups are enabled
     *
     * @return bool
     */
    protected function shouldBackup(): bool
    {
        return $this->enableBackup;
    }

    /**
     * Determines if the auto-flush mode is enabled
     *
     * @return bool
     */
    protected function autoFlush(): bool
    {
        return $this->autoFlush;
    }

    /**
     * Asserts that the model is mutable (not append-only).
     *
     * By default, models are mutable. The AppendOnly trait overrides this
     * to enforce append-only restrictions when used.
     *
     * @return void
     */
    protected function assertMutable(): void
    {
        // Default: no restriction, model is fully mutable
    }
}
