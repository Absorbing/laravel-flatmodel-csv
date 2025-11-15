<?php

namespace FlatModel\CsvModel\Tests\Unit;

use FlatModel\CsvModel\Models\Model;
use FlatModel\CsvModel\Traits\HeaderAware;
use FlatModel\CsvModel\Tests\TestCase;
use FlatModel\CsvModel\Exceptions\HeaderMismatchException;

class HeaderAwareTest extends TestCase
{
    /** @test */
    public function it_validates_headers_in_strict_mode()
    {
        // Headers match - should succeed
        $model = new class extends Model {
            use HeaderAware;

            protected string $path;
            protected bool $strictHeaders = true;
            protected array $headers = ['id', 'name', 'email', 'active'];

            public function __construct() {
                $this->path = __DIR__ . '/../Fixtures/users.csv';
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }
        };

        $this->assertTrue(true); // If we get here, validation passed
    }

    /** @test */
    public function it_throws_exception_when_headers_mismatch_in_strict_mode()
    {
        $this->expectException(HeaderMismatchException::class);
        $this->expectExceptionMessage('Headers do not match expected values');

        new class extends Model {
            use HeaderAware;

            protected string $path;
            protected bool $strictHeaders = true;
            protected array $headers = ['id', 'wrong', 'headers', 'here']; // Wrong headers

            public function __construct() {
                $this->path = __DIR__ . '/../Fixtures/users.csv';
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }
        };
    }

    /** @test */
    public function it_does_not_validate_when_strict_mode_disabled()
    {
        // Even with wrong headers, should not throw when strictHeaders=false
        $model = new class extends Model {
            use HeaderAware;

            protected string $path;
            protected bool $strictHeaders = false; // Not strict
            protected array $headers = ['wrong', 'headers', 'here', 'test'];

            public function __construct() {
                $this->path = __DIR__ . '/../Fixtures/users.csv';
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }
        };

        // Should use the CSV headers since strict mode is off and fall back to parent
        $headers = $model->getHeaders();
        $this->assertEquals(['id', 'name', 'email', 'active'], $headers);
    }

    /** @test */
    public function it_provides_has_header_helper_method()
    {
        $model = new class extends Model {
            use HeaderAware;

            protected string $path;

            public function __construct() {
                $this->path = __DIR__ . '/../Fixtures/users.csv';
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }
        };

        $this->assertTrue($model->hasHeader('name'));
        $this->assertTrue($model->hasHeader('email'));
        $this->assertFalse($model->hasHeader('nonexistent'));
    }

    /** @test */
    public function it_detects_missing_headers()
    {
        $this->expectException(HeaderMismatchException::class);
        $this->expectExceptionMessage('Headers do not match');

        new class extends Model {
            use HeaderAware;

            protected string $path;
            protected bool $strictHeaders = true;
            protected array $headers = ['id', 'name', 'email', 'active', 'extra_column']; // Extra column

            public function __construct() {
                $this->path = __DIR__ . '/../Fixtures/users.csv';
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }
        };
    }

    /** @test */
    public function it_detects_extra_headers()
    {
        $this->expectException(HeaderMismatchException::class);
        $this->expectExceptionMessage('Headers do not match');

        new class extends Model {
            use HeaderAware;

            protected string $path;
            protected bool $strictHeaders = true;
            protected array $headers = ['id', 'name', 'email']; // Missing 'active' column

            public function __construct() {
                $this->path = __DIR__ . '/../Fixtures/users.csv';
                parent::__construct();
            }

            protected function resolvePath($path = ''): string {
                return $this->path;
            }
        };
    }
}
