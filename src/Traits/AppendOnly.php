<?php

namespace FlatModel\CsvModel\Traits;

use FlatModel\CsvModel\Exceptions\AppendOnlyViolationException;

trait AppendOnly
{
    /**
     * Ensures that the model is not append-only or throws an exception if it is.
     *
     * This is used to prevent modifications to models configured as append-only.
     *
     * @return void
     * @throws AppendOnlyViolationException If the model is append-only
     */
    protected function assertAppendable(): void
    {
        if (!$this->isAppendOnly()) {
            throw new AppendOnlyViolationException(static::class . ' is append-only and does not support mutation.');
        }
    }

    abstract protected function isAppendOnly(): bool;
}