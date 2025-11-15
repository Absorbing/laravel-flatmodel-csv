<?php

namespace FlatModel\CsvModel\Traits;

use FlatModel\CsvModel\Exceptions\FileNotFoundException;
use FlatModel\CsvModel\Exceptions\InvalidHandleException;
use FlatModel\CsvModel\Exceptions\PrimaryKeyMissingException;
use FlatModel\CsvModel\Exceptions\StreamOpenException;
use FlatModel\CsvModel\Exceptions\InvalidRowFormatException;
use FlatModel\CsvModel\Exceptions\MissingHeaderException;

trait LoadsFromSource
{
    /**
     * The file handle or stream resource.
     */
    protected mixed $handle;

    /**
     * Initializes the model by loading data from a CSV file.
     *
     * The first row of the CSV file is expected to contain column headers.
     * Each subsequent row is stored as an associative array where keys are the column names.
     *
     * @throws FileNotFoundException If the file does not exist at the resolved path
     * @throws PrimaryKeyMissingException If a primary key is specified but not found in the CSV row
     */
    public function __construct()
    {
        $this->handle = $this->isStream()
            ? $this->openStreamHandle()
            : $this->openFileHandle();

        $this->loadFromHandle();
    }

    /**
     * Loads CSV data from an open stream or file handle.
     *
     * @param resource $handle
     */
    protected function loadFromHandle(): void
    {
        if (!$this->isStream()) {
            $this->validateFileHandle($this->handle);
        }

        $this->loadHeadersFromHandle($this->handle);

        $rows = [];
        $headers = $this->getHeaders();
        $expected = count($headers);

        while (($line = fgetcsv($this->handle, 0, $this->getDelimiter(), $this->getEnclosure(),
                $this->getEscape())) !== false) {
            if (count($line) !== $expected) {
                throw new InvalidRowFormatException(sprintf(
                    'CSV row does not match header count. Expected %d columns, got %d. Row: [%s]',
                    $expected,
                    count($line),
                    implode(', ', $line)
                ));
            }

            $row = array_combine($headers, $line);

            if ($row === false) {
                throw new InvalidRowFormatException('Failed to combine headers and row values.');
            }

            $rows[] = $this->castRow($row);
        }

        $this->setRows($rows);

        fclose($this->handle);
    }

    /**
     * Loads the headers from the provided file handle.
     *
     * Behavior depends on $hasHeaders property:
     * - If true: Reads first row as headers (unless pre-defined)
     * - If false AND headers provided: Uses provided headers
     * - If false AND no headers: Generates numeric indices from first data row
     *
     * @param resource $handle The file handle to read the CSV headers from.
     * @return array<int,string> The loaded headers as an array.
     * @throws MissingHeaderException If headers cannot be determined
     */
    protected function loadHeadersFromHandle($handle): array
    {
        // If headers are pre-defined, use them
        if (!empty($this->getHeaders())) {
            // If CSV has header row but we're using custom headers, skip the first line
            if ($this->hasHeaders()) {
                fgetcsv($handle, 0, $this->getDelimiter(), $this->getEnclosure(), $this->getEscape());
            }
            return $this->getHeaders();
        }

        // If CSV has header row, read it
        if ($this->hasHeaders()) {
            $headers = fgetcsv(
                $handle,
                0,
                $this->getDelimiter(),
                $this->getEnclosure(),
                $this->getEscape()
            );

            if (!is_array($headers)) {
                throw new MissingHeaderException("Failed to read headers from CSV file.");
            }

            $this->setHeaders(array_map('trim', $headers));
            return $this->getHeaders();
        }

        // No header row and no pre-defined headers: generate numeric indices
        // Peek at first row to determine column count
        $firstRow = fgetcsv($handle, 0, $this->getDelimiter(), $this->getEnclosure(), $this->getEscape());

        if (!is_array($firstRow)) {
            throw new MissingHeaderException("Cannot determine column count - CSV file appears empty.");
        }

        // Rewind to beginning so first row gets processed as data
        rewind($handle);

        // Generate numeric column indices
        $numericHeaders = array_map('strval', range(0, count($firstRow) - 1));
        $this->setHeaders($numericHeaders);

        return $this->getHeaders();
    }

    /**
     * Opens a file path.
     *
     * @return mixed
     * @throws FileNotFoundException
     */
    protected function openFileHandle(): mixed
    {
        $path = $this->resolvePath();

        if (!file_exists($path)) {
            throw new FileNotFoundException("File not found at $path");
        }

        return fopen($path, 'r');
    }

    /**
     * Opens a stream resource.
     *
     * @throws StreamOpenException
     */
    protected function openStreamHandle(): void
    {
        throw new StreamOpenException(static::class . ' is in stream mode but did not implement openStreamHandle().');
    }

    /**
     * Validates handle type for file mode
     *
     * @param $handle
     * @return void
     * @throws InvalidHandleException
     */
    protected function validateFileHandle($handle): void
    {
        if (!is_resource($handle)) {
            throw new InvalidHandleException('File handle is not valid.');
        }
    }

    /**
     * Resolves and returns the specific file or directory path.
     *
     * @return string The resolved path as a string.
     */
    abstract protected function resolvePath(): string;

    /**
     * Returns the delimiter character used to separate fields in the CSV file.
     *
     * @return string The delimiter character.
     */
    abstract protected function getDelimiter(): string;

    /**
     * Returns the enclosure character used to wrap field values in the CSV file.
     *
     * @return string The enclosure character.
     */
    abstract protected function getEnclosure(): string;

    /**
     * Returns the escape character used in the CSV file.
     *
     * @return string The escape character.
     */
    abstract protected function getEscape(): string;

    /**
     * Casts row values according to the defined casting rules in $cast property.
     *
     * @param array<string,mixed> $row Associative array representing a CSV row
     * @return array<string,mixed> The row with values cast to their specified types
     */
    abstract protected function castRow(array $row): array;

    /**
     * Retrieves the headers from the CSV file.
     *
     * @return array<int,string> The headers as an array of strings
     */
    abstract protected function getHeaders(): array;

    /**
     * Sets the headers for the CSV file.
     *
     * @param array<int,string> $headers Array of column headers
     * @return static Returns the current instance for method chaining
     */
    abstract protected function setHeaders(array $headers): static;

    /**
     * Returns the complete set of data rows for querying.
     *
     * @return array<int,array<string,mixed>> Array of rows where each row is an associative array
     */
    abstract protected function getRows(): array;

    /**
     * Sets the data rows for the model.
     *
     * @param array $rows
     * @return static
     */
    abstract protected function setRows(array $rows): static;

    /**
     * Checks if the model is in stream mode.
     *
     * @return bool True if the model is in stream mode, false otherwise
     */
    abstract protected function isStream(): bool;

    /**
     * Checks if the CSV has a header row.
     *
     * @return bool True if the CSV has a header row, false otherwise
     */
    abstract protected function hasHeaders(): bool;

    /**
     * Checks if the model is writable.
     *
     * @return bool True if the model is writable, false otherwise
     */
    abstract protected function isWritable(): bool;
}
