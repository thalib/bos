<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Test001BasicTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_returns_successful_response_for_products_endpoint()
    {
        // Create some test products based on TDD approach for /api/v1/products
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
                        'price',
                        'description',
                        'categories',
                        'slug',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'pagination' => [
                    'totalItems',
                    'currentPage',
                    'itemsPerPage',
                    'totalPages',
                    'urlPath',
                    'urlQuery',
                    'nextPage',
                    'prevPage',
                ],
                'search',
                'sort',
                'filters',
                'schema',
                'columns',
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals('Resources retrieved successfully', $response->json('message'));
        $this->assertCount(3, $response->json('data'));
    }

    #[Test]
    public function it_handles_search_parameter()
    {
        // Create products with specific names
        Product::factory()->create(['name' => 'iPhone 14']);
        Product::factory()->create(['name' => 'Samsung Galaxy']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?search=iPhone');

        $response->assertStatus(200);

        $data = $response->json('data');
        if (count($data) > 0) {
            $iphoneProducts = collect($data)->filter(function ($product) {
                return str_contains($product['name'], 'iPhone');
            });

            $this->assertGreaterThan(0, $iphoneProducts->count());
        }
        $this->assertEquals('iPhone', $response->json('search'));
    }

    #[Test]
    public function it_handles_pagination_parameters()
    {
        // Create more products than the default page size
        Product::factory()->count(20)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?per_page=5');

        $response->assertStatus(200);

        $pagination = $response->json('pagination');
        $this->assertEquals(5, $pagination['itemsPerPage']);
        $this->assertEquals(20, $pagination['totalItems']);
        $this->assertCount(5, $response->json('data'));
    }
}
