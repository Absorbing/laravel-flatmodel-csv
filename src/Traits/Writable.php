<?php

namespace FlatModel\CsvModel\Traits;

use RuntimeException;

trait Writable
{
    /**
     * Check if the model is writable
     *
     * @return void
     */
    protected function assertWritable(): void
    {
        if (! $this->isWritable()) {
            throw new RuntimeException('This model is not writable.');
        }

        if($this->isStream()) {
            throw new RuntimeException(static::class . ' is a stream model, it cannot be written to.');
        }
    }

    /**
     * Add a row to the model.
     *
     * @param array $row
     * @return $this
     */
    public function add(array $row): static
    {
        $this->assertWritable();

        $row = $this->castRow($row);

        $this->setRow($row);
        return $this;
    }

    /**
     * Update rows in the model based on a filter and mutator.
     *
     * @param callable $filter
     * @param callable $mutator
     * @return $this
     */
    public function update(callable $filter, callable $mutator): static
    {
        $this->assertWritable();
        $this->assertAppendable();

        $rows = $this->getRows();
        $updated = false;

        foreach ($rows as $index => $row) {
            if ($filter($row)) {
                $row = $this->castRow($mutator($row));

                $rows[$index] = $row;
                $updated = true;
            }
        }

        if ($updated) {
            $this->setRows($rows);
        }

        return $this;
    }

    /**
     * Delete rows from the model based on a filter.
     *
     * @param callable $filter A function that takes a row and returns true if it should be deleted
     * @return $this
     */
    public function delete(callable $filter): static
    {
        $this->assertWritable();
        $this->assertAppendable();

        $rows = $this->getRows();

        $filteredRows = array_values(
            array_filter($rows, function ($row) use ($filter) {
                return ! $filter($row);
            })
        );

        if (count($filteredRows) !== count($rows)) {
            $this->setRows($filteredRows);
        }

        return $this;
    }

    /**
     * Save the model to its storage.
     *
     * This method writes the current state of the model to its underlying storage,
     * such as a file or database, depending on the implementation.
     *
     * @return static Returns the current instance for method chaining
     * @throws RuntimeException If the model is not writable or is a stream model
     */
    public function save(): static
    {
        $this->assertWritable();

        if ($this->isStream()) {
            throw new RuntimeException(static::class . ' is a stream model, it cannot be saved.');
        }

        $this->flush();

        return $this;
    }

    /**
     * Flush the model's data to its storage.
     *
     * This method writes all rows in the model to the underlying storage,
     * such as a file, ensuring that the data is persisted.
     *
     * @return static Returns the current instance for method chaining
     * @throws RuntimeException If the model is not writable or is a stream model
     */
    public function flush(): static
    {
        $this->assertWritable();

        if ($this->isStream()) {
            throw new RuntimeException(static::class . ' is a stream model, it cannot be flushed.');
        }

        $rows = $this->getRows();

        if (empty($rows)) {
            return $this;
        }

        $handle = fopen($this->resolvePath(), 'w');
        if ($handle === false) {
            throw new RuntimeException('Failed to open file for writing: ' . $this->resolvePath());
        }

        $headers = $this->getHeaders();
        if (!empty($headers)) {
            fputcsv($handle, $headers, $this->getDelimiter(), $this->getEnclosure(), $this->getEscape());
        }

        foreach ($rows as $row) {
            fputcsv($handle, array_values($row), $this->getDelimiter(), $this->getEnclosure(), $this->getEscape());
        }

        fclose($handle);

        return $this;
    }

    /**
     * Checks if the model is writable.
     *
     * @return bool
     */
    abstract protected function isWritable(): bool;

    /**
     * Sets a single row in the model.
     *
     * @param array $row The row to set
     * @return static
     */
    abstract protected function setRow(array $row): static;

    /**
     * Sets the rows for the model.
     *
     * @param array $rows The rows to set
     * @return static
     */
    abstract protected function setRows(array $rows): static;

    /**
     * Returns the rows for the model.
     *
     * @return array<int,array<string,mixed>> The rows as an array of associative arrays
     */
    abstract protected function getRows(): array;

    /**
     * Casts a row to the appropriate types based on the model's casting rules.
     *
     * @param array<string,mixed> $row The row to cast
     * @return array<string,mixed> The casted row
     */
    abstract protected function castRow(array $row): array;

    /**
     * Checks if the model is in stream mode.
     *
     * @return bool
     */
    abstract protected function isStream(): bool;

    /**
     * Resolves the path where the model's data is stored.
     *
     * @return string The resolved file path
     */
    abstract protected function assertAppendable(): void;
}