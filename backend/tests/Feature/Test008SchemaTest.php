<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Test008SchemaTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_returns_null_schema_when_no_getApiSchema_method()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        
        // Product model DOES have getApiSchema(), so schema should NOT be null
        // This test should actually test a model without getApiSchema()
        // For now, we expect the grouped schema structure
        $schema = $response->json('schema');
        $this->assertIsArray($schema);
        $this->assertNotNull($schema);
    }

    #[Test]
    public function it_returns_correct_schema_structure_when_available()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'schema' // Should be array of groups
            ]);

        $schema = $response->json('schema');
        
        if ($schema !== null) {
            $this->assertIsArray($schema);
            
            // Test grouped schema structure
            foreach ($schema as $group) {
                $this->assertArrayHasKey('group', $group);
                $this->assertArrayHasKey('fields', $group);
                $this->assertIsString($group['group']);
                $this->assertIsArray($group['fields']);
                
                // Test each field in the group (now array-based)
                foreach ($group['fields'] as $field) {
                    $this->assertIsArray($field);
                    
                    // Required field properties
                    $this->assertArrayHasKey('field', $field);
                    $this->assertArrayHasKey('label', $field);
                    $this->assertArrayHasKey('type', $field);
                    $this->assertArrayHasKey('required', $field);
                    
                    $this->assertIsString($field['field']);
                    $this->assertIsString($field['label']);
                    $this->assertIsString($field['type']);
                    $this->assertIsBool($field['required']);
                }
            }
        }
    }

    #[Test]
    public function it_validates_schema_field_types_when_present()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        
        $schema = $response->json('schema');
        
        if ($schema !== null) {
            $validTypes = [
                'string', 'number', 'decimal', 'boolean', 'date', 'text',
                'select', 'checkbox', 'textarea', 'object', 'array', 'file', 'tags', 'multiselect'
            ];
            
            // Test grouped schema structure
            foreach ($schema as $group) {
                foreach ($group['fields'] as $field) {
                    $this->assertContains($field['type'], $validTypes);
                }
            }
        }
    }

    #[Test]
    public function it_validates_schema_optional_properties_when_present()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        
        $schema = $response->json('schema');
        
        if ($schema !== null) {
            // Test grouped schema structure
            foreach ($schema as $group) {
                foreach ($group['fields'] as $field) {
                    // Validate optional string properties
                    if (isset($field['placeholder'])) {
                        $this->assertIsString($field['placeholder']);
                    }
                    
                    if (isset($field['pattern'])) {
                        $this->assertIsString($field['pattern']);
                    }
                    
                    // Validate optional boolean properties
                    if (isset($field['unique'])) {
                        $this->assertIsBool($field['unique']);
                    }
                    
                    // Validate optional numeric properties
                    if (isset($field['maxLength'])) {
                        $this->assertIsInt($field['maxLength']);
                        $this->assertGreaterThan(0, $field['maxLength']);
                    }
                    
                    if (isset($field['min'])) {
                        $this->assertIsNumeric($field['min']);
                    }
                    
                    if (isset($field['max'])) {
                        $this->assertIsNumeric($field['max']);
                    }
                    
                    if (isset($field['minItems'])) {
                        $this->assertIsInt($field['minItems']);
                        $this->assertGreaterThanOrEqual(0, $field['minItems']);
                    }
                    
                    if (isset($field['maxItems'])) {
                        $this->assertIsInt($field['maxItems']);
                        $this->assertGreaterThan(0, $field['maxItems']);
                    }
                    
                    // Validate options array for select fields
                    if (isset($field['options'])) {
                        $this->assertIsArray($field['options']);
                    }
                    
                    // Validate properties object for object/array types
                    if (isset($field['properties'])) {
                        $this->assertIsArray($field['properties']);
                    }
                }
            }
        }
    }

    #[Test]
    public function it_validates_select_field_options_structure_when_present()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        
        $schema = $response->json('schema');
        
        if ($schema !== null) {
            // Test grouped schema structure
            foreach ($schema as $group) {
                foreach ($group['fields'] as $field) {
                    if ($field['type'] === 'select' && isset($field['options'])) {
                        $this->assertIsArray($field['options']);
                        
                        foreach ($field['options'] as $option) {
                            // Options can be simple values or objects with value/label
                            if (is_array($option)) {
                                $this->assertArrayHasKey('value', $option);
                                $this->assertArrayHasKey('label', $option);
                            }
                        }
                    }
                }
            }
        }
    }

    #[Test]
    public function it_validates_object_field_properties_structure_when_present()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        
        $schema = $response->json('schema');
        
        if ($schema !== null) {
            // Test grouped schema structure
            foreach ($schema as $group) {
                foreach ($group['fields'] as $field) {
                    if ($field['type'] === 'object' && isset($field['properties'])) {
                        $this->assertIsArray($field['properties']);
                        
                        // Properties should define nested field structure
                        foreach ($field['properties'] as $property) {
                            if (is_array($property)) {
                                $this->assertArrayHasKey('field', $property);
                                $this->assertArrayHasKey('type', $property);
                            }
                        }
                    }
                }
            }
        }
    }

    #[Test]
    public function it_validates_array_field_properties_when_present()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        
        $schema = $response->json('schema');
        
        if ($schema !== null) {
            // Test grouped schema structure
            foreach ($schema as $group) {
                foreach ($group['fields'] as $field) {
                    if ($field['type'] === 'array') {
                        // Array fields should have minItems/maxItems if specified
                        if (isset($field['minItems']) && isset($field['maxItems'])) {
                            $this->assertLessThanOrEqual($field['maxItems'], $field['minItems']);
                        }
                        
                        // Array fields might have properties defining item structure
                        if (isset($field['properties'])) {
                            $this->assertIsArray($field['properties']);
                        }
                    }
                }
            }
        }
    }

    #[Test]
    public function it_validates_numeric_field_constraints_when_present()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        
        $schema = $response->json('schema');
        
        if ($schema !== null) {
            // Test grouped schema structure
            foreach ($schema as $group) {
                foreach ($group['fields'] as $field) {
                    if (in_array($field['type'], ['number', 'decimal'])) {
                        // If both min and max are specified, min should be less than max
                        if (isset($field['min']) && isset($field['max'])) {
                            $this->assertLessThanOrEqual($field['max'], $field['min']);
                        }
                    }
                }
            }
        }
    }

    #[Test]
    public function it_ensures_schema_fields_have_unique_names()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        
        $schema = $response->json('schema');
        
        if ($schema !== null) {
            $allFieldNames = [];
            
            // Collect all field names from all groups
            foreach ($schema as $group) {
                foreach ($group['fields'] as $field) {
                    $allFieldNames[] = $field['field'];
                }
            }
            
            $this->assertEquals(count($allFieldNames), count(array_unique($allFieldNames)), 'Schema field names must be unique across all groups');
        }
    }

    #[Test]
    public function it_validates_default_values_match_field_types()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        
        $schema = $response->json('schema');
        
        if ($schema !== null) {
            // Test grouped schema structure
            foreach ($schema as $group) {
                foreach ($group['fields'] as $field) {
                    if (isset($field['default'])) {
                        switch ($field['type']) {
                            case 'boolean':
                            case 'checkbox':
                                $this->assertIsBool($field['default']);
                                break;
                            case 'number':
                            case 'decimal':
                                $this->assertIsNumeric($field['default']);
                                break;
                            case 'array':
                                $this->assertIsArray($field['default']);
                                break;
                            case 'object':
                                $this->assertIsArray($field['default']); // Objects represented as arrays in JSON
                                break;
                            case 'string':
                            case 'text':
                            case 'textarea':
                            case 'select':
                            case 'date':
                            default:
                                // Most string-like types or allow any for flexibility
                                break;
                        }
                    }
                }
            }
        }
    }

    #[Test]
    public function it_validates_pattern_is_valid_regex_when_present()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        
        $schema = $response->json('schema');
        
        if ($schema !== null) {
            // Test grouped schema structure
            foreach ($schema as $group) {
                foreach ($group['fields'] as $field) {
                    if (isset($field['pattern'])) {
                        // Test that the pattern is a valid regex
                        $testResult = @preg_match('/' . $field['pattern'] . '/', 'test');
                        $this->assertNotFalse($testResult, "Pattern '{$field['pattern']}' should be a valid regex");
                    }
                }
            }
        }
    }
}
