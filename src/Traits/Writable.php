<?php

namespace FlatModel\CsvModel\Traits;

use FlatModel\CsvModel\Exceptions\AppendOnlyViolationException;
use FlatModel\CsvModel\Exceptions\FileWriteException;
use FlatModel\CsvModel\Exceptions\StreamWriteException;
use FlatModel\CsvModel\Exceptions\WriteNotAllowedException;

/**
 * Provides write capabilities for CSV models.
 *
 * This trait is opt-in and should be used by models that need to
 * modify their underlying CSV data.
 *
 * Usage:
 * ```php
 * class EditableModel extends Model
 * {
 *     use Writable;
 *
 *     protected bool $writable = true;
 * }
 * ```
 *
 * Available methods:
 * - insert(): Add new rows
 * - update(): Modify existing rows
 * - upsert(): Update or insert
 * - delete(): Remove rows
 * - flush()/save(): Persist changes to disk
 */
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
     * Updates rows using either callable or array-based syntax.
     *
     * @param callable|array $filterOrConditions Callable filter or array of key-value match conditions
     * @param callable|array $mutatorOrUpdates Callable mutator or array of updates to merge
     * @return static
     */
    public function update(callable|array $filterOrConditions, callable|array $mutatorOrUpdates): static
    {
        $this->assertWritable();
        $this->assertMutable();


        $rows = $this->getRows();
        $updated = false;

        // Normalize inputs
        $filter = is_callable($filterOrConditions)
            ? $filterOrConditions
            : fn($row) => $this->matchesConditions($row, $filterOrConditions);

        $mutator = is_callable($mutatorOrUpdates)
            ? $mutatorOrUpdates
            : fn($row) => [...$row, ...$mutatorOrUpdates];

        foreach ($rows as $index => $row) {
            if ($filter($row)) {
                $rows[$index] = $this->castRow($mutator($row));
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
     * Updates the first matching row or inserts a new row if no match is found.
     *
     * Accepts either callable or array-based syntax for conditions and data.
     *
     * @param callable|array $filterOrConditions Callable filter or array of conditions
     * @param callable|array $mutatorOrData Callable mutator or data to insert/update
     * @return static
     *
     * @throws WriteNotAllowedException
     * @throws AppendOnlyViolationException
     * @throws StreamWriteException
     */
    public function upsert(callable|array $filterOrConditions, callable|array $mutatorOrData): static
    {
        $this->assertWritable();
        $this->assertMutable();

        $rows = $this->getRows();
        $updated = false;

        $filter = is_callable($filterOrConditions)
            ? $filterOrConditions
            : fn($row) => $this->matchesConditions($row, $filterOrConditions);

        $mutator = is_callable($mutatorOrData)
            ? $mutatorOrData
            : fn($row) => empty($row) ? $mutatorOrData : [...$row, ...$mutatorOrData];

        foreach ($rows as $index => $row) {
            if ($filter($row)) {
                $rows[$index] = $this->castRow($mutator($row));
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            $rows[] = $this->castRow($mutator([]));
        }

        $this->setRows($rows);

        if ($this->autoFlush()) {
            $this->flush();
        }

        return $this;
    }


    /**
     * Deletes rows using either a callable or array-based conditions.
     *
     * @param callable|array $filter Callable filter or array of conditions
     * @return static
     *
     * @throws WriteNotAllowedException
     * @throws AppendOnlyViolationException
     * @throws StreamWriteException
     */
    public function delete(callable|array $filter): static
    {
        $this->assertWritable();
        $this->assertMutable();

        $rows = $this->getRows();

        $filterFn = is_callable($filter)
            ? $filter
            : fn($row) => $this->matchesConditions($row, $filter);

        $filteredRows = array_values(array_filter($rows, fn($row) => !$filterFn($row)));

        if (count($filteredRows) !== count($rows)) {
            $this->setRows($filteredRows);
        }

        if ($this->autoFlush()) {
            $this->flush();
        }

        return $this;
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
        return $this->flush();
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
     * Asserts that the model is mutable (not append-only).
     *
     * @return void
     */
    abstract protected function assertMutable(): void;

    /**
     * Determines whether the model should automatically flush data after mutations.
     *
     * @return bool True if auto-flush is enabled, false otherwise
     */
    abstract protected function autoFlush(): bool;
}