<?php

namespace FlatModel\CsvModel\Traits;

use FlatModel\CsvModel\Exceptions\ColumnNotFoundException;
use Illuminate\Support\Collection;

trait Queryable
{
    /**
     * Stores query constraints for filtering data.
     * Used to build complex queries with multiple conditions.
     *
     * @var array<callable> Array of filter callbacks
     */
    protected array $queryConstraints = [];

    /**
     * Columns to select from the data rows.
     *
     * @var array<string>|null
     */
    protected ?array $selectedColumns = null;

    /**
     * Adds a where clause to filter rows based on column value.
     *
     * @param string $column The column name to filter on
     * @param string $value The value to match against
     * @return static Returns the current instance for method chaining
     */
    public function where(string $column, mixed $value): static
    {
        $this->queryConstraints[] = fn($row) => ($row[$column] ?? null) == $value;
        return $this;
    }

    /**
     * Retrieves filtered rows based on applied constraints.
     * Gets all rows from the data source, applies any query constraints that have been added,
     * then resets the constraints and returns the filtered results as a Collection.
     * Each constraint is applied sequentially using array_filter.
     *
     * @return \Illuminate\Support\Collection Collection containing the filtered rows
     */
    public function get(): Collection
    {
        $filtered = $this->getRows();

        foreach ($this->queryConstraints as $constraint) {
            $filtered = array_filter($filtered, $constraint);
        }

        $this->queryConstraints = [];

        if ($this->selectedColumns !== null) {
            $filtered = array_map(function ($row) {
                return array_intersect_key(
                    $this->castRow($row),
                    array_flip($this->selectedColumns)
                );
            }, $filtered);

            $this->selectedColumns = null;
        }

        return collect(array_values($filtered));
    }

    /**
     * Select specific columns from the data rows.
     *
     * @param string ...$columns The column names to select
     * @return static Returns the current instance for method chaining
     */
    public function select(string ...$columns): static
    {
        $this->selectedColumns = $columns;
        return $this;
    }

    /**
     * Retrieves the first row that matches the applied constraints.
     *
     * @return array|null The first matching row or null if no match found
     */
    public function first(): ?array
    {
        $result = $this->get();
        return $result->isEmpty() ? null : $result->first();
    }

    /**
     * Retrieves a Collection containing only the values from a single column of the CSV data.
     *
     * @param string $column The name of the column to extract values from
     * @return Collection A collection containing only the values from the specified column
     * @throws ColumnNotFoundException If the specified column does not exist in the CSV file
     */
    public function pluck(string $column): Collection
    {
        return $this->get()->pluck($column);
    }

    /**
     * Retrieves the first value from a specified column in the CSV data.
     *
     * @param string $column The name of the column to extract the value from
     * @return mixed The first value from the specified column, or null if not found
     */
    public function value(string $column): mixed
    {
        $values = $this->pluck($column);

        return $values->isEmpty() ? null : $values->first();
    }

    /**
     * Returns the complete set of data rows for querying.
     * This abstract method must be implemented by classes using this trait
     * to provide access to their underlying data storage.
     *
     * @return array<int,array<string,mixed>> Array of rows where each row is an associative array
     */
    abstract protected function getRows(): array;

    /**
     * Casts row values according to the defined casting rules in $cast property.
     *
     * @param array<string,mixed> $row Associative array representing a CSV row
     * @return array<string,mixed> The row with values cast to their specified types
     */
    abstract protected function castRow(array $row): array;
}