<?php

namespace FlatModel\CsvModel\Traits;

trait ResolvesPrimaryKey
{
    /**
     * @var string|null The name of the primary key column or null if not set
     */
    protected ?string $primaryKey;

    /**
     * Get the primary key column name.
     *
     * @return ?string The primary key column name if set, null otherwise
     */
    protected function getPrimaryKey(): ?string
    {
        return $this->primaryKey;
    }

    /**
     * Set the primary key column name.
     *
     * @param string $key The column name to use as primary key
     * @return static The current instance for method chaining
     */
    public function setPrimaryKey(string $key): static
    {
        $this->primaryKey = $key;
        return $this;
    }

    /**
     * Check if a primary key is defined.
     *
     * @return bool True if a primary key is set, false otherwise
     */
    protected function hasPrimaryKey(): bool
    {
        return !empty($this->primaryKey);
    }

    /**
     * Searches for a record in the rows based on the provided value and primary key.
     *
     * @param string $value The value to search for in the rows using the primary key.
     * @return array|null Returns the matching row as an associative array if found, or null if no match is found.
     */
    public function find(mixed $value): ?array
    {
        if (!$this->hasPrimaryKey()) {
            throw new \LogicException("Cannot call find() without defining a primary key.");
        }

        foreach ($this->getRows() as $row) {
            if (($row[$this->primaryKey] ?? null) == $value) {
                return $this->castRow($row);
            }
        }

        return null;
    }

    /**
     * Validates the presence of the primary key in the CSV headers.
     *
     * @throws \RuntimeException If the primary key is not found in the CSV headers
     */
    protected function validatePrimaryKeyPresence(): void
    {
        if ($this->hasPrimaryKey() && !isset($this->headers[$this->primaryKey])) {
            throw new \RuntimeException("Primary key '{$this->primaryKey}' not found in CSV headers");
        }
    }

    /**
     * Casts row values according to the defined casting rules in $cast property.
     *
     * @param array<string,mixed> $row Associative array representing a CSV row
     * @return array<string,mixed> The row with values cast to their specified types
     */
    abstract protected function castRow(array $row): array;

    /**
     * Returns the complete set of data rows for querying.
     * This abstract method must be implemented by classes using this trait
     * to provide access to their underlying data storage.
     *
     * @return array<int,array<string,mixed>> Array of rows where each row is an associative array
     */
    abstract protected function getRows(): array;
}