<?php

namespace Absorbing\CsvModel\Traits;

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
     * Returns the complete set of data rows for querying.
     * This abstract method must be implemented by classes using this trait
     * to provide access to their underlying data storage.
     *
     * @return array<int,array<string,mixed>> Array of rows where each row is an associative array
     */
    abstract protected function getRows(): array;

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

        foreach($this->queryConstraints as $constraint) {
            $filtered = array_filter($filtered, $constraint);
        }

        $this->queryConstraints = [];
        return collect(array_values($filtered));
    }
}