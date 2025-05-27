<?php

namespace FlatModel\CsvModel\Traits;

use FlatModel\CsvModel\Exceptions\AppendOnlyViolationException;
use FlatModel\CsvModel\Exceptions\FileWriteException;
use FlatModel\CsvModel\Exceptions\StreamWriteException;
use FlatModel\CsvModel\Exceptions\WriteNotAllowedException;

trait Writable
{
    /**
     * Check if the model is writable
     *
     * @return void
     * @throws WriteNotAllowedException
     * @throws StreamWriteException
     */
    protected function assertWritable(): void
    {
        if (!$this->isWritable()) {
            throw new WriteNotAllowedException('This model is not writable.');
        }

        if ($this->isStream()) {
            throw new StreamWriteException(static::class . ' is a stream model, it cannot be written to.');
        }
    }

    /**
     * Inserts a single row into the model's dataset.
     *
     * This method applies casting rules to the provided row and appends it to the current data.
     * If auto-flush is enabled, the data will be immediately persisted to storage.
     *
     * @param array<string, mixed> $row The row to insert
     * @return static
     *
     * @throws WriteNotAllowedException If the model is not writable
     * @throws StreamWriteException If the model is in stream mode
     */
    public function insert(array $row): static
    {
        $this->assertWritable();

        $row = $this->castRow($row);

        $this->setRow($row);

        if ($this->autoFlush()) {
            $this->flush();
        }

        return $this;
    }

    /**
     * Updates all rows that match the given filter by applying a mutator callback.
     *
     * This method is flexible and allows complex logic via callables.
     * If auto-flush is enabled, changes are saved immediately.
     *
     * @param callable(array<string, mixed>): bool $filter A function that returns true for rows to be updated
     * @param callable(array<string, mixed>): array $mutator A function that returns the updated row
     * @return static
     *
     * @throws WriteNotAllowedException If the model is not writable
     * @throws AppendOnlyViolationException If the model is append-only
     * @throws StreamWriteException If the model is in stream mode
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

        if ($this->autoFlush()) {
            $this->flush();
        }

        return $this;
    }

    /**
     * Updates all rows that match the given conditions with the provided key-value updates.
     *
     * This is the array-based equivalent of update(), allowing you to specify conditions and updates
     * as associative arrays. Only exact matches on the given conditions will be modified.
     *
     * @param array $conditions Key-value pairs used to locate existing rows (e.g., ['id' => 5])
     * @param array $updates Key-value pairs to merge into the matched rows (e.g., ['name' => 'Updated'])
     * @return static
     */
    public function updateWhere(array $conditions, array $updates): static
    {
        return $this->update(
            fn($row) => $this->matchesConditions($row, $conditions),
            fn($row) => [...$row, ...$updates]
        );
    }

    /**
     * Updates the first row that matches the filter or inserts a new row if none match.
     *
     * The mutator will receive either the matched row or an empty array (if inserting).
     * If auto-flush is enabled, changes are saved immediately.
     *
     * @param callable(array<string, mixed>): bool $filter A function that returns true for the row to update
     * @param callable(array<string, mixed>): array $mutator A function that builds the row to insert or update
     * @return static
     *
     * @throws WriteNotAllowedException If the model is not writable
     * @throws AppendOnlyViolationException If the model is append-only
     * @throws StreamWriteException If the model is in stream mode
     */
    public function upsert(callable $filter, callable $mutator): static
    {
        $this->assertWritable();
        $this->assertAppendable();

        $rows = $this->getRows();
        $updated = false;

        foreach ($rows as $index => $row) {
            if ($filter($row)) {
                $rows[$index] = $this->castRow($mutator($row));
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            $newRow = $this->castRow($mutator([]));
            $rows[] = $newRow;
        }

        $this->setRows($rows);

        if ($this->autoFlush()) {
            $this->flush();
        }

        return $this;
    }

    /**
     * Updates an existing row matching the given conditions, or inserts a new row if none match.
     *
     * This is the array-based equivalent of upsert() and mimics Eloquent-style conditional behavior.
     * If no existing row matches the conditions, a new row will be created using the insertOrUpdate array.
     *
     * @param array $conditions Key-value pairs used to locate an existing row (e.g., ['id' => 5])
     * @param array $insertOrUpdate Key-value pairs used to either update an existing row or insert a new one
     * @return static
     */
    public function upsertWhere(array $conditions, array $insertOrUpdate): static
    {
        return $this->upsert(
            fn($row) => $this->matchesConditions($row, $conditions),
            fn($row) => empty($row) ? $insertOrUpdate : [...$row, ...$insertOrUpdate]
        );
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
                return !$filter($row);
            })
        );

        if (count($filteredRows) !== count($rows)) {
            $this->setRows($filteredRows);
        }

        if ($this->autoFlush()) {
            $this->flush();
        }

        return $this;
    }

    /**
     * Deletes all rows that match the given conditions.
     *
     * This is the array-based equivalent of delete(), using simple key-value pairs to match rows.
     * Any rows with exact matches to all provided conditions will be removed.
     *
     * @param array $conditions Key-value pairs used to locate rows for deletion (e.g., ['status' => 'inactive'])
     * @return static
     */
    public function deleteWhere(array $conditions): static
    {
        return $this->delete(
            fn($row) => $this->matchesConditions($row, $conditions)
        );
    }

    /**
     * Alias of flush() method.
     *
     * This method writes all rows in the model to the underlying storage,
     * such as a file, ensuring that the data is persisted.
     *
     * @return static Returns the current instance for method chaining
     * @throws StreamWriteException If the model is not writable or is a stream model
     */
    public function save(): static
    {
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
     * @throws StreamWriteException If the model is a stream model
     * @throws FileWriteException If the model isn't writeable
     */
    public function flush(): static
    {
        $this->assertWritable();

        if ($this->isStream()) {
            throw new StreamWriteException(static::class . ' is a stream model, it cannot be flushed.');
        }

        $rows = $this->getRows();

        if (empty($rows)) {
            return $this;
        }

        $handle = fopen($this->resolvePath(), 'w');
        if ($handle === false) {
            throw new FileWriteException('Failed to open file for writing: ' . $this->resolvePath());
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
     * Determines if a row matches all given conditions exactly.
     *
     * Performs a strict equality check on each key-value pair.
     *
     * @param array<string, mixed> $row The row to evaluate
     * @param array<string, mixed> $conditions The conditions to match (e.g., ['id' => 5])
     * @return bool True if the row satisfies all conditions, false otherwise
     */
    private function matchesConditions(array $row, array $conditions): bool
    {
        foreach ($conditions as $key => $value) {
            if (($row[$key] ?? null) !== $value) {
                return false;
            }
        }
        return true;
    }

    /**
     * Indicates whether the model supports writing to its underlying storage.
     *
     * @return bool True if the model is writable, false if it is read-only
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

    /**
     * Determines whether the model should automatically flush data after mutations.
     *
     * @return bool True if auto-flush is enabled, false otherwise
     */
    abstract protected function autoFlush(): bool;
}