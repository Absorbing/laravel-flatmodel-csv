<?php

namespace FlatModel\CsvModel\Tests\Unit;

use FlatModel\CsvModel\Models\Model;
use FlatModel\CsvModel\Tests\TestCase;

class QueryableTest extends TestCase
{
    private function getTestModel()
    {
        return new class extends Model {
            protected string $path;

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
    public function it_filters_rows_with_where_clause()
    {
        $model = $this->getTestModel();

        $results = $model->where('name', 'John Doe')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('John Doe', $results[0]['name']);
    }

    /** @test */
    public function it_chains_multiple_where_clauses()
    {
        $model = $this->getTestModel();

        $results = $model
            ->where('active', 'true')
            ->where('id', '1')
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals('John Doe', $results[0]['name']);
    }

    /** @test */
    public function it_returns_first_matching_row()
    {
        $model = $this->getTestModel();

        $result = $model->where('active', 'true')->first();

        $this->assertEquals('John Doe', $result['name']);
    }

    /** @test */
    public function it_returns_null_when_no_match_found()
    {
        $model = $this->getTestModel();

        $result = $model->where('name', 'Nonexistent')->first();

        $this->assertNull($result);
    }

    /** @test */
    public function it_plucks_column_values()
    {
        $model = $this->getTestModel();

        $names = $model->pluck('name');

        $this->assertCount(5, $names);
        $this->assertContains('John Doe', $names->toArray());
        $this->assertContains('Jane Smith', $names->toArray());
    }

    /** @test */
    public function it_plucks_with_where_filter()
    {
        $model = $this->getTestModel();

        $names = $model->where('active', 'true')->pluck('name');

        $this->assertCount(3, $names);
        $this->assertContains('John Doe', $names->toArray());
        $this->assertNotContains('Jane Smith', $names->toArray());
    }

    /** @test */
    public function it_gets_single_value()
    {
        $model = $this->getTestModel();

        $email = $model->where('name', 'John Doe')->value('email');

        $this->assertEquals('john@example.com', $email);
    }

    /** @test */
    public function it_returns_null_when_value_not_found()
    {
        $model = $this->getTestModel();

        $email = $model->where('name', 'Nonexistent')->value('email');

        $this->assertNull($email);
    }

    /** @test */
    public function it_selects_specific_columns()
    {
        $model = $this->getTestModel();

        $results = $model->select('id', 'name')->get();

        $this->assertCount(5, $results);
        $this->assertArrayHasKey('id', $results[0]);
        $this->assertArrayHasKey('name', $results[0]);
        $this->assertArrayNotHasKey('email', $results[0]);
    }

    /** @test */
    public function it_combines_select_and_where()
    {
        $model = $this->getTestModel();

        $results = $model
            ->select('id', 'name')
            ->where('active', 'true')
            ->get();

        $this->assertCount(3, $results);
        $this->assertArrayHasKey('id', $results[0]);
        $this->assertArrayNotHasKey('email', $results[0]);
    }

    /** @test */
    public function it_returns_all_rows_without_filters()
    {
        $model = $this->getTestModel();

        $results = $model->get();

        $this->assertCount(5, $results);
    }
}
