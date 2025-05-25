<?php

namespace FlatModel\CsvModel\Traits;

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
     * @throws \RuntimeException If the file does not exist at the resolved path
     * @throws \RuntimeException If a primary key is specified but not found in the CSV row
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

        while (($line = fgetcsv($this->handle, 0, $this->getDelimiter(), $this->getEnclosure(), $this->getEscape())) !== false) {
            $rows[] = $this->castRow(array_combine($this->getHeaders(), $line));
        }

        $this->setRows($rows);

        fclose($this->handle);
    }

    /**
     * Opens a file path.
     *
     * @return resource
     */
    protected function openFileHandle(): mixed
    {
        $path = $this->resolvePath();

        if (!file_exists($path)) {
            throw new RuntimeException("File not found at $path");
        }

        return fopen($path, 'r');
    }

    /**
     * Opens a stream resource.
     *
     * @return resource
     */
    protected function openStreamHandle(): mixed
    {
        throw new LogicException(static::class . ' is in stream mode but did not implement openStreamHandle().');
    }

    /**
     * Validates handle type for file mode
     *
     * @param $handle
     * @return void
     */
    protected function validateFileHandle($handle): void
    {
        if (!is_resource($handle)) {
            throw new InvalidArgumentException('File handle is not valid.');
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
     * Returns the complete set of data rows for querying.
     *
     * @return array<int,array<string,mixed>> Array of rows where each row is an associative array
     */
    abstract protected function getRows(): array;

    /**
     * Loads the headers from the provided file handle and sets them for the instance.
     *
     * @param resource $handle The file handle to read the CSV headers from.
     * @return array<int,string> The loaded headers as an array.
     */
    abstract protected function loadHeadersFromHandle($handle): array;

}