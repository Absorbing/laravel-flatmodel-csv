<?php

namespace FlatModel\CsvModel\Traits;

use FlatModel\CsvModel\Exceptions\HeaderMismatchException;

/**
 * Provides strict header validation and header utility methods.
 *
 * This trait is opt-in and adds:
 * - Strict header validation against expected headers
 * - hasHeader() helper method
 *
 * Without this trait, headers are still loaded but not validated.
 *
 * Usage:
 * ```php
 * class StrictModel extends Model
 * {
 *     use HeaderAware;
 *
 *     protected array $headers = ['id', 'name', 'email'];
 *     protected bool $strictHeaders = true;
 * }
 * ```
 */
trait HeaderAware
{
    /**
     * Checks if a specific header exists in the list of headers.
     *
     * @param string $header The name of the header to check for existence
     * @return bool True if the header exists, false otherwise
     */
    public function hasHeader(string $header): bool
    {
        return in_array($header, $this->getHeaders(), $this->isStrictHeaders());
    }

    /**
     * Loads and validates headers from the CSV file.
     *
     * Overrides the base implementation to add strict header validation.
     *
     * @param resource $handle The file handle to read the CSV headers from.
     * @return array<int,string> The loaded headers as an array.
     * @throws HeaderMismatchException If strict validation is enabled and headers don't match.
     */
    protected function loadHeadersFromHandle($handle): array
    {
        // Get current file position before reading
        $position = ftell($handle);

        // If we have pre-defined headers and strict mode
        if (!empty($this->headers) && $this->isStrictHeaders() && $this->hasHeaders()) {
            // Read the actual header row from CSV
            $csvHeaders = fgetcsv(
                $handle,
                0,
                $this->getDelimiter(),
                $this->getEnclosure(),
                $this->getEscape()
            );

            if (!is_array($csvHeaders)) {
                throw new HeaderMismatchException("Failed to read headers from CSV file.");
            }

            // Validate against expected headers
            $this->validateHeaders(array_map('trim', $csvHeaders));

            // Headers are valid - they match our expected headers
            return $this->getHeaders();
        }

        // Restore position and use parent implementation
        fseek($handle, $position);
        return parent::loadHeadersFromHandle($handle);
    }

    /**
     * Validates the headers against the expected headers.
     *
     * @param array<int,string> $csvHeaders The headers read from CSV
     * @throws HeaderMismatchException If headers don't match expected values.
     */
    protected function validateHeaders(array $csvHeaders): void
    {
        if ($this->isStrictHeaders()) {
            $missingHeaders = array_diff($this->headers, $csvHeaders);
            $extraHeaders = array_diff($csvHeaders, $this->headers);

            if (!empty($missingHeaders) || !empty($extraHeaders)) {
                throw new HeaderMismatchException(
                    'Headers do not match expected values. ' .
                    'Expected: [' . implode(', ', $this->headers) . '], ' .
                    'Found: [' . implode(', ', $csvHeaders) . '].'
                );
            }
        }
    }

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
     * Returns the array of headers read from the CSV file.
     *
     * @return array<int,string> Array of column headers
     */
    abstract public function getHeaders(): array;

    /**
     * Checks if strict header validation is enabled.
     *
     * @return bool True if strict header validation is enabled, false otherwise
     */
    abstract public function isStrictHeaders(): bool;

    /**
     * Checks if the CSV has a header row.
     *
     * @return bool True if the CSV has a header row, false otherwise
     */
    abstract protected function hasHeaders(): bool;
}
