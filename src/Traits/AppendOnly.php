<?php

namespace FlatModel\CsvModel\Traits;

use FlatModel\CsvModel\Exceptions\AppendOnlyViolationException;

/**
 * Enforces append-only behavior on models.
 *
 * When this trait is used, the model will only allow insert operations.
 * Update, upsert, and delete operations will throw an AppendOnlyViolationException.
 *
 * Usage:
 * ```php
 * class LogModel extends Model
 * {
 *     use Writable, AppendOnly;
 *
 *     protected bool $writable = true;
 *     protected bool $appendOnly = true;
 * }
 * ```
 */
trait AppendOnly
{
    /**
     * Asserts that the model is mutable (not append-only).
     *
     * Overrides the base Model's implementation to throw an exception
     * when the model is configured as append-only.
     *
     * @return void
     * @throws AppendOnlyViolationException If the model is append-only
     */
    protected function assertMutable(): void
    {
        if ($this->isAppendOnly()) {
            throw new AppendOnlyViolationException(
                static::class . ' is append-only and does not support mutation of existing rows.'
            );
        }
    }

    abstract protected function isAppendOnly(): bool;
}
