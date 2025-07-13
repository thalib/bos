<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Test002successTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_returns_successful_response_structure()
    {
        // Create some test products
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
                'pagination',
                'search',
                'sort',
                'filters',
                'schema',
                'columns',
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertIsString($response->json('message'));
    }

    #[Test]
    public function it_returns_successful_response_for_single_product()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals($product->id, $response->json('data.id'));
    }

    #[Test]
    public function it_returns_successful_response_for_product_creation()
    {
        $productData = [
            'name' => 'Test Product',
            'description' => 'A test product description',
            'price' => 99.99,
            'categories' => ['Electronics'],
            'slug' => 'test-product',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products', $productData);

        $response->assertStatus(201);
        $this->assertTrue($response->json('success'));
        $this->assertEquals('Resource created successfully', $response->json('message'));
        $this->assertEquals($productData['name'], $response->json('data.name'));
    }

    #[Test]
    public function it_returns_successful_response_for_product_update()
    {
        $product = Product::factory()->create();
        $updateData = ['name' => 'Updated Product Name'];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/{$product->id}", $updateData);

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals('Resource updated successfully', $response->json('message'));
        $this->assertEquals($updateData['name'], $response->json('data.name'));
    }

    #[Test]
    public function it_returns_successful_response_for_product_deletion()
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(204);
        // Note: 204 No Content responses typically don't have a body, so we don't check for JSON response
    }
}
