<?php

namespace FlatModel\CsvModel\Tests\Unit;

use FlatModel\CsvModel\Models\Model;
use FlatModel\CsvModel\Tests\TestCase;

class CastableTest extends TestCase
{
    /** @test */
    public function it_casts_integer_values()
    {
        $model = new class extends Model {
            protected string $path;
            protected array $cast = [
                'id' => 'int',
                'age' => 'int',
            ];

            public function __construct() {
                $this->path = __DIR__ . '/../Fixtures/types.csv';
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }

            public function getPublicRows() { return $this->getRows(); }
        };

        $rows = $model->getPublicRows();

        $this->assertIsInt($rows[0]['id']);
        $this->assertEquals(1, $rows[0]['id']);
        $this->assertIsInt($rows[0]['age']);
        $this->assertEquals(25, $rows[0]['age']);
    }

    /** @test */
    public function it_casts_float_values()
    {
        $model = new class extends Model {
            protected string $path;
            protected array $cast = [
                'price' => 'float',
            ];

            public function __construct() {
                $this->path = __DIR__ . '/../Fixtures/types.csv';
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }

            public function getPublicRows() { return $this->getRows(); }
        };

        $rows = $model->getPublicRows();

        $this->assertIsFloat($rows[0]['price']);
        $this->assertEquals(19.99, $rows[0]['price']);
    }

    /** @test */
    public function it_casts_boolean_values()
    {
        $model = new class extends Model {
            protected string $path;
            protected array $cast = [
                'active' => 'bool',
            ];

            public function __construct() {
                $this->path = __DIR__ . '/../Fixtures/types.csv';
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }

            public function getPublicRows() { return $this->getRows(); }
        };

        $rows = $model->getPublicRows();

        $this->assertIsBool($rows[0]['active']);
        $this->assertTrue($rows[0]['active']);
        $this->assertFalse($rows[1]['active']);
    }

    /** @test */
    public function it_casts_string_values()
    {
        $model = new class extends Model {
            protected string $path;
            protected array $cast = [
                'id' => 'string',
                'name' => 'string',
            ];

            public function __construct() {
                $this->path = __DIR__ . '/../Fixtures/types.csv';
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }

            public function getPublicRows() { return $this->getRows(); }
        };

        $rows = $model->getPublicRows();

        $this->assertIsString($rows[0]['id']);
        $this->assertEquals('1', $rows[0]['id']);
    }

    /** @test */
    public function it_handles_multiple_type_casts()
    {
        $model = new class extends Model {
            protected string $path;
            protected array $cast = [
                'id' => 'int',
                'age' => 'int',
                'price' => 'float',
                'active' => 'bool',
                'name' => 'string',
            ];

            public function __construct() {
                $this->path = __DIR__ . '/../Fixtures/types.csv';
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }

            public function getPublicRows() { return $this->getRows(); }
        };

        $rows = $model->getPublicRows();

        $this->assertIsInt($rows[0]['id']);
        $this->assertIsInt($rows[0]['age']);
        $this->assertIsFloat($rows[0]['price']);
        $this->assertIsBool($rows[0]['active']);
        $this->assertIsString($rows[0]['name']);
    }
}
