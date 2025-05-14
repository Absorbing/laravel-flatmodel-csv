<?php

namespace Absorbing\CsvModel;

use Illuminate\Support\ServiceProvider;
use Absorbing\CsvModel\Console\MakeCsvModel;

class CsvModelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeCsvModel::class,
            ]);

            $this->publishes([
                __DIR__ . '/Console/stubs/csvmodel.stub' => base_path('stubs/csvmodel.stub'),
            ], 'csvmodel-stubs');
        }
    }
}
