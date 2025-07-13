<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Test003PaginationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_returns_correct_pagination_structure()
    {
        // Create exactly 25 products to test pagination
        Product::factory()->count(25)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'pagination' => [
                    'totalItems',
                    'currentPage',
                    'itemsPerPage',
                    'totalPages',
                    'urlPath',
                    'urlQuery',
                    'nextPage',
                    'prevPage',
                ]
            ]);

        $pagination = $response->json('pagination');
        $this->assertEquals(25, $pagination['totalItems']);
        $this->assertEquals(1, $pagination['currentPage']);
        $this->assertEquals(10, $pagination['itemsPerPage']);
        $this->assertEquals(3, $pagination['totalPages']);
        $this->assertNotNull($pagination['urlPath']);
        $this->assertNull($pagination['urlQuery']);
        $this->assertNotNull($pagination['nextPage']);
        $this->assertNull($pagination['prevPage']);
    }

    #[Test]
    public function it_handles_page_parameter()
    {
        Product::factory()->count(25)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?page=2&per_page=10');

        $response->assertStatus(200);
        
        $pagination = $response->json('pagination');
        $this->assertEquals(2, $pagination['currentPage']);
        $this->assertNotNull($pagination['nextPage']);
        $this->assertNotNull($pagination['prevPage']);
        $this->assertCount(10, $response->json('data'));
    }

    #[Test]
    public function it_handles_per_page_parameter()
    {
        Product::factory()->count(20)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?per_page=5');

        $response->assertStatus(200);
        
        $pagination = $response->json('pagination');
        $this->assertEquals(5, $pagination['itemsPerPage']);
        $this->assertEquals(4, $pagination['totalPages']);
        $this->assertCount(5, $response->json('data'));
    }

    #[Test]
    public function it_enforces_max_per_page_limit()
    {
        Product::factory()->count(10)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?per_page=150'); // Over the 100 limit

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_PARAMETERS'
                ]
            ]);
    }

    #[Test]
    public function it_uses_default_pagination_when_no_parameters()
    {
        Product::factory()->count(20)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        
        $pagination = $response->json('pagination');
        $this->assertEquals(1, $pagination['currentPage']);
        $this->assertEquals(15, $pagination['itemsPerPage']); // Default per_page
        $this->assertCount(15, $response->json('data'));
    }

    #[Test]
    public function it_handles_invalid_page_parameter()
    {
        Product::factory()->count(10)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?page=0');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_PARAMETERS'
                ]
            ]);
    }

    #[Test]
    public function it_handles_invalid_per_page_parameter()
    {
        Product::factory()->count(10)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?per_page=0');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_PARAMETERS'
                ]
            ]);
    }

    #[Test]
    public function it_returns_empty_data_for_page_beyond_total()
    {
        Product::factory()->count(5)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?page=10&per_page=10');

        $response->assertStatus(200);
        
        $this->assertCount(0, $response->json('data'));
        $pagination = $response->json('pagination');
        $this->assertEquals(10, $pagination['currentPage']);
        $this->assertEquals(5, $pagination['totalItems']);
    }
}
