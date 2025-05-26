<?php

namespace FlatModel\CsvModel\Traits;

trait Castable
{
    /**
     * Casts row values according to the defined casting rules in $cast property.
     *
     * @param array<string,mixed> $row Associative array representing a CSV row
     * @return array<string,mixed> The row with values cast to their specified types
     */
    protected function castRow(array $row): array
    {
        foreach ($this->getCast() as $key => $type) {
            if (isset($row[$key])) {
                $row[$key] = match ($type) {
                    'int' => (int)$row[$key],
                    'float' => (float)$row[$key],
                    'bool' => (bool)$row[$key],
                    'string' => (string)$row[$key],
                    default => $row[$key],
                };
            }
        }

        return $row;
    }

    /**
     * Casts a value to the specified type.
     *
     * @param mixed $value The value to cast
     * @param string $type The type to cast to (e.g., 'int', 'float', 'bool', 'string')
     * @return mixed The casted value
     */
    protected function castValue(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'int', 'integer' => (int)$value,
            'float', 'double' => (float)$value,
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'string' => (string)$value,
            'datetime' => \Carbon\Carbon::parse($value),
            default => $value,
        };
    }

    /**
     * Checks if a column has a defined cast type.
     *
     * @param string $column The name of the column to check
     * @return bool True if the column has a cast type, false otherwise
     */
    protected function hasCast(string $column): bool
    {
        return isset($this->getCast()[$column]);
    }

    /**
     * Retrieves the casted value for a specific column in a row.
     *
     * @param array<string,mixed> $row The row data
     * @param string $column The name of the column to retrieve
     * @return mixed The casted value for the specified column
     */
    protected function getCastedField(array $row, string $column): mixed
    {
        $value = $row[$column] ?? null;

        if ($this->hasCast($column)) {
            return $this->castValue($value, $this->getCast()[$column]);
        }

        return $value;
    }

    /**
     * Returns the casted types for the model's columns.
     *
     * @return array<string,string>
     */
    abstract protected function getCast(): array;
}