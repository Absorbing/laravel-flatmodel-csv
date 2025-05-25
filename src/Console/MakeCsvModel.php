<?php

namespace FlatModel\CsvModel\Console;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class MakeCsvModel extends GeneratorCommand
{
    /**
     * The name of the command used to generate a CSV model.
     *
     * @var string
     */
    protected $name = 'make:csv-model';
    
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'make:csv-model {name?} {--path=} {--primary=?}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new CSV-backed model class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'CsvModel';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        return __DIR__ . '/stubs/csv-model.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Models';
    }

    protected function getOptions(): array
    {
        return [
            ['path', null, InputOption::VALUE_OPTIONAL, 'The CSV path to set inside the model', null],
            ['primary', null, InputOption::VALUE_OPTIONAL, 'The primary key to set inside the model', null],
        ];
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'name' => fn () => $this->ask('What should the CSV Model be named?'),
        ];
    }

    protected function buildClass($name): string
    {
        $class = parent::buildClass($name);

        $csvPath = $this->option('path') ?? 'csv/' . class_basename($name) . '.csv';
        $primaryKey = $this->option('primary') ?? null;

        return str_replace([
                'DummyCsvPath',
                'DummyPrimaryKey',
            ],
            [
                addslashes($csvPath),
                addslashes($primaryKey),
            ],
        $class
        );
    }
}