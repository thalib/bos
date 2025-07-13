<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Test010ResponseStructureTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_returns_consistent_response_structure_for_index()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'pagination' => [
                    'totalItems',
                    'currentPage',
                    'itemsPerPage',
                    'totalPages'
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotNull($response->json('message'));
    }

    #[Test]
    public function it_returns_consistent_response_structure_for_show()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotNull($response->json('message'));
    }

    #[Test]
    public function it_returns_consistent_response_structure_for_create()
    {
        $productData = [
            'name' => 'Test Product',
            'slug' => 'test-product',
            'type' => 'simple',
            'price' => 99.99,
            'publication_status' => 'published'
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products', $productData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotNull($response->json('message'));
    }

    #[Test]
    public function it_returns_consistent_response_structure_for_update()
    {
        $product = Product::factory()->create();
        $updateData = ['name' => 'Updated Product Name'];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/{$product->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotNull($response->json('message'));
    }

    #[Test]
    public function it_returns_consistent_response_structure_for_delete()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(204);
    }

    #[Test]
    public function it_returns_consistent_grouped_schema_structure()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'schema' => [
                    '*' => [
                        'group',
                        'fields'
                    ]
                ]
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotNull($response->json('message'));

        // Validate grouped schema structure
        $schema = $response->json('schema');
        $this->assertIsArray($schema);

        foreach ($schema as $group) {
            $this->assertArrayHasKey('group', $group);
            $this->assertArrayHasKey('fields', $group);
            $this->assertIsArray($group['fields']);

            foreach ($group['fields'] as $field) {
                $this->validateSchemaField($field);
            }
        }
    }

    #[Test]
    public function it_returns_consistent_error_structure()
    {
        // Test unauthenticated request
        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(401)
            ->assertJsonStructure([
                'success',
                'message',
                'error' => [
                    'code',
                    'message',
                    'details'
                ]
            ]);

        $this->assertFalse($response->json('success'));
        $this->assertNotNull($response->json('message'));
        $this->assertNotNull($response->json('error.code'));
        $this->assertNotNull($response->json('error.message'));
        $this->assertIsArray($response->json('error.details'));
    }

    #[Test]
    public function it_returns_consistent_validation_error_structure()
    {
        // Send invalid data to trigger validation error
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products', ['invalid' => 'data']);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'error' => [
                    'code',
                    'details'
                ]
            ]);

        $this->assertFalse($response->json('success'));
        $this->assertNotNull($response->json('message'));
        $this->assertEquals('VALIDATION_FAILED', $response->json('error.code'));
    }

    private function validateSchemaField(array $field): void
    {
        $this->assertArrayHasKey('field', $field);
        $this->assertArrayHasKey('type', $field);
        $this->assertIsString($field['field']);
        $this->assertIsString($field['type']);

        // Validate type is one of the allowed types
        $allowedTypes = [
            'string', 'text', 'email', 'password', 'url', 'tel',
            'number', 'integer', 'decimal', 'float',
            'boolean', 'checkbox',
            'date', 'datetime', 'time',
            'select', 'radio', 'multiselect',
            'file', 'image',
            'array', 'object', 'json', 'textarea', 'tags'
        ];
        $this->assertContains($field['type'], $allowedTypes, "Invalid field type: {$field['type']}");

        // Optional properties that may be present
        $optionalProperties = [
            'field', 'label', 'placeholder', 'help', 'required', 'readonly',
            'default', 'min', 'max', 'step', 'minlength', 'maxlength', 'maxLength',
            'pattern', 'options', 'multiple', 'accept', 'properties', 'unique',
            'minItems', 'maxItems', 'prefix', 'suffix', 'order', 'attributes'
        ];

        foreach ($field as $key => $value) {
            if (!in_array($key, ['type']) && !in_array($key, $optionalProperties)) {
                $this->fail("Unexpected field property: {$key}");
            }
        }

        // Type-specific validations
        if (isset($field['options'])) {
            $this->assertIsArray($field['options']);
            foreach ($field['options'] as $option) {
                if (is_array($option)) {
                    $this->assertArrayHasKey('value', $option);
                    $this->assertArrayHasKey('label', $option);
                }
            }
        }

        if (isset($field['properties'])) {
            $this->assertIsArray($field['properties']);
        }

        if (isset($field['min'])) {
            $this->assertIsNumeric($field['min']);
        }

        if (isset($field['max'])) {
            $this->assertIsNumeric($field['max']);
        }

        if (isset($field['required'])) {
            $this->assertIsBool($field['required']);
        }

        if (isset($field['readonly'])) {
            $this->assertIsBool($field['readonly']);
        }
    }
}
