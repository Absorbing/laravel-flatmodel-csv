<?php

namespace FlatModel\CsvModel\Traits;

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
     * Loads the headers from the provided file handle and sets them for the instance.
     *
     * @param resource $handle The file handle to read the CSV headers from.
     * @return array<int,string> The loaded headers as an array.
     * @throws \RuntimeException If strict header validation is enabled and the headers are invalid.
     */
    protected function loadHeadersFromHandle($handle): array
    {
        if ($this->headers) {
            return $this->getHeaders();
        }

        $headers = fgetcsv(
            $handle,
            0,
            $this->getDelimiter(),
            $this->getEnclosure(),
            $this->getEscape()
        );

        if(!is_array($headers)) {
            throw new \RuntimeException("Failed to read headers from CSV file.");
        }

        $this->validateHeaders($headers);

        $this->setHeaders(array_map('trim', $headers));

        return $this->getHeaders();
    }

    /**
     * Validates the headers against the defined rules.
     *
     * @param array<int,string> $headers The headers to validate
     * @throws \RuntimeException If strict header validation is enabled and the headers are invalid.
     */
    protected function validateHeaders(array $headers): void
    {
        if ($this->strictHeaders) {
            $missingHeaders = array_diff($this->headers, $headers);
            if (!empty($missingHeaders)) {
                throw new \RuntimeException(
                    'Headers do not match expected values. ' .
                    'Expected: [' . implode(', ', $this->headers) . '], ' .
                    'Found: [' . implode(', ', $headers) . '].'
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
     * Sets the headers for the CSV file.
     *
     * @param array<int,string> $headers Array of column headers
     * @return static Returns the current instance for method chaining
     */
    abstract public function setHeaders(array $headers): static;

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
}