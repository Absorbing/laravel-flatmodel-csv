<?php

namespace FlatModel\CsvModel\Traits;

use FlatModel\CsvModel\Exceptions\AppendOnlyViolationException;

trait AppendOnly
{
    /**
     * Ensure the model is not being mutated in a restricted way.
     *
     * @throws AppendOnlyViolationException
     */
    protected function assertAppendable(): void
    {
        if (!$this->isAppendOnly()) {
            throw new AppendOnlyViolationException(static::class . ' is append-only and does not support mutation.');
        }
    }

    abstract protected function isAppendOnly(): bool;
}