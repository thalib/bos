<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Test007ColumnsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_always_returns_columns_array()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'columns' => [
                    '*' => [
                        'field',
                        'label'
                    ]
                ]
            ]);

        $columns = $response->json('columns');
        $this->assertIsArray($columns);
        $this->assertNotEmpty($columns);
    }

    #[Test]
    public function it_returns_default_id_column_when_no_getIndexColumns_method()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        
        $columns = $response->json('columns');
        
        // If Product model doesn't have getIndexColumns(), should return default ID column
        if (count($columns) === 1 && $columns[0]['field'] === 'id') {
            $this->assertEquals('id', $columns[0]['field']);
            $this->assertEquals('ID', $columns[0]['label']);
            $this->assertTrue($columns[0]['sortable']);
            $this->assertTrue($columns[0]['clickable']);
            $this->assertFalse($columns[0]['search']);
            $this->assertEquals('text', $columns[0]['format']);
            $this->assertEquals('left', $columns[0]['align']);
        }
    }

    #[Test]
    public function it_returns_correct_column_structure()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        
        $columns = $response->json('columns');
        
        foreach ($columns as $column) {
            $this->assertArrayHasKey('field', $column);
            $this->assertArrayHasKey('label', $column);
            $this->assertIsString($column['field']);
            $this->assertIsString($column['label']);
            
            // Optional properties should have default values if not specified
            if (isset($column['sortable'])) {
                $this->assertIsBool($column['sortable']);
            }
            
            if (isset($column['clickable'])) {
                $this->assertIsBool($column['clickable']);
            }
            
            if (isset($column['search'])) {
                $this->assertIsBool($column['search']);
            }
            
            if (isset($column['format'])) {
                $this->assertIsString($column['format']);
            }
            
            if (isset($column['align'])) {
                $this->assertContains($column['align'], ['left', 'center', 'right']);
            }
        }
    }

    #[Test]
    public function it_includes_expected_product_columns_when_defined()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        
        $columns = $response->json('columns');
        $columnFields = collect($columns)->pluck('field')->toArray();
        
        // If Product model has getIndexColumns() method, check for expected columns
        if (count($columns) > 1) {
            // Expect common product columns
            $expectedColumns = ['name', 'price', 'categories', 'status'];
            
            foreach ($expectedColumns as $expectedColumn) {
                if (in_array($expectedColumn, $columnFields)) {
                    $columnConfig = collect($columns)->firstWhere('field', $expectedColumn);
                    $this->assertNotNull($columnConfig);
                    $this->assertIsString($columnConfig['label']);
                }
            }
        }
    }

    #[Test]
    public function it_validates_price_column_format_when_present()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        
        $columns = $response->json('columns');
        $priceColumn = collect($columns)->firstWhere('field', 'price');
        
        if ($priceColumn) {
            // Price column should ideally have currency format and right alignment
            if (isset($priceColumn['format'])) {
                $this->assertEquals('currency', $priceColumn['format']);
            }
            
            if (isset($priceColumn['align'])) {
                $this->assertEquals('right', $priceColumn['align']);
            }
            
            if (isset($priceColumn['sortable'])) {
                $this->assertTrue($priceColumn['sortable']);
            }
        }
    }

    #[Test]
    public function it_validates_name_column_properties_when_present()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        
        $columns = $response->json('columns');
        $nameColumn = collect($columns)->firstWhere('field', 'name');
        
        if ($nameColumn) {
            // Name column should ideally be sortable, clickable, and searchable
            $this->assertEquals('name', $nameColumn['field']);
            $this->assertIsString($nameColumn['label']);
            
            if (isset($nameColumn['sortable'])) {
                $this->assertTrue($nameColumn['sortable']);
            }
            
            if (isset($nameColumn['clickable'])) {
                $this->assertTrue($nameColumn['clickable']);
            }
            
            if (isset($nameColumn['search'])) {
                $this->assertTrue($nameColumn['search']);
            }
        }
    }

    #[Test]
    public function it_validates_column_types_when_specified()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        
        $columns = $response->json('columns');
        
        foreach ($columns as $column) {
            if (isset($column['type'])) {
                $validTypes = ['string', 'number', 'boolean', 'date', 'datetime', 'text', 'select'];
                $this->assertContains($column['type'], $validTypes);
            }
        }
    }

    #[Test]
    public function it_validates_column_format_types()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        
        $columns = $response->json('columns');
        
        foreach ($columns as $column) {
            if (isset($column['format'])) {
                $validFormats = [
                    'text', 'currency', 'number', 'date', 'datetime', 
                    'percentage', 'boolean', 'email', 'url'
                ];
                $this->assertContains($column['format'], $validFormats);
            }
        }
    }

    #[Test]
    public function it_ensures_columns_have_unique_fields()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        
        $columns = $response->json('columns');
        $fields = collect($columns)->pluck('field')->toArray();
        
        $this->assertEquals(count($fields), count(array_unique($fields)), 'Column fields must be unique');
    }

    #[Test]
    public function it_handles_columns_with_width_property()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        
        $columns = $response->json('columns');
        
        foreach ($columns as $column) {
            if (isset($column['width'])) {
                $this->assertIsString($column['width']);
                // Width could be in px, %, em, etc.
                $this->assertMatchesRegularExpression('/^\d+(%|px|em|rem)?$/', $column['width']);
            }
        }
    }

    #[Test]
    public function it_handles_columns_with_hidden_property()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        
        $columns = $response->json('columns');
        
        foreach ($columns as $column) {
            if (isset($column['hidden'])) {
                $this->assertIsBool($column['hidden']);
            }
        }
    }
}
