<?php

namespace FlatModel\CsvModel\Models;

use FlatModel\CsvModel\Traits\Castable;
use FlatModel\CsvModel\Traits\LoadsFromSource;
use FlatModel\CsvModel\Traits\Queryable;
use FlatModel\CsvModel\Traits\ResolvesPrimaryKey;

abstract class Model
{
    use Queryable,
        ResolvesPrimaryKey,
        LoadsFromSource,
        Castable,
        HeaderAware;

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
    protected array $rows = [];

    /**
     * Array of column headers from the CSV file.
     * Each element represents a column name in the order they appear.
     *
     * @var array<int,string>
     */
    protected array $headers = [];

    /**
     * Whether to use strict header checking.
     *
     * @var bool
     */
    protected bool $strictHeaders = false;

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

    protected function resolvePath($path = ''): string
    {
        return storage_path($this->path);
    }

    /**
     * Checks whether this model operates in stream mode.
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
     * Checks if strict header validation is enabled.
     *
     * @return bool
     */
    protected function isStrictHeaders(): bool
    {
        return $this->strictHeaders;
    }
}
