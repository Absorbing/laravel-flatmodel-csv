<?php

namespace FlatModel\CsvModel\Traits;

use FlatModel\CsvModel\Exceptions\BackupFailedException;

/**
 * Provides automatic backup functionality before write operations.
 *
 * This trait is opt-in and creates timestamped backup copies of CSV files
 * before any modifications are written to disk.
 *
 * Usage:
 * ```php
 * class BackedUpModel extends Model
 * {
 *     use Writable, Backupable;
 *
 *     protected bool $writable = true;
 *     protected bool $enableBackup = true;
 * }
 * ```
 */
trait Backupable
{
    protected function backup(): void
    {
        if (!$this->shouldBackup() || $this->isStream()) {
            return;
        }

        $path = $this->resolvePath();

        if (!file_exists($path)) {
            return;
        }

        $backupPath = $path . '.' . date('Y-m-d-H-i-s') . '.bak';

        if (!copy($path, $backupPath)) {
            throw new BackupFailedException("Failed to backup $path to $backupPath");
        }
    }

    /**
     * Determines if backups are enabled
     *
     * @return bool
     */
    abstract protected function shouldBackup(): bool;

    /**
     * Checks whether this model operates in stream mode.
     *
     * @return bool True if the model is in stream mode, false otherwise
     */
    abstract protected function isStream(): bool;

    /**
     * Resolves a relative or absolute file path.
     *
     * @param string $path Optional path to resolve relative to the model's base path
     * @return string The fully resolved absolute file path
     */
    abstract protected function resolvePath(): string;
}