<?php

namespace FlatModel\CsvModel\Traits;

/**
 * Core functionality trait that combines all essential features for CSV model operations.
 *
 * This trait is used internally by the Model class and should not be used directly
 * by end users. It provides the fundamental capabilities for loading, querying,
 * and managing CSV data.
 *
 * @internal
 */
trait HasCoreFeatures
{
    use LoadsFromSource,
        Castable,
        Queryable,
        ResolvesPrimaryKey;
}
