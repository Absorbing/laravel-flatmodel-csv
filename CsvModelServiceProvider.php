<?php

use Absorbing\CsvModel\Console\MakeCsvModel;
public function register(): void
{
    if ($this->app->runningInConsole()) {
        $this->commands([
            MakeCsvModel::class,
        ]);
    }
}