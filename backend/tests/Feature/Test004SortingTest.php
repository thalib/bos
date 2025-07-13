<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Test004SortingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_returns_correct_sorting_structure()
    {
        Product::factory()->count(5)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?sort=name&dir=asc');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'sort' => [
                    'column',
                    'dir'
                ]
            ]);

        $sort = $response->json('sort');
        $this->assertEquals('name', $sort['column']);
        $this->assertEquals('asc', $sort['dir']);
    }

    #[Test]
    public function it_sorts_by_name_ascending()
    {
        Product::factory()->create(['name' => 'Zebra Product']);
        Product::factory()->create(['name' => 'Alpha Product']);
        Product::factory()->create(['name' => 'Beta Product']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?sort=name&dir=asc');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertEquals('Alpha Product', $data[0]['name']);
        $this->assertEquals('Beta Product', $data[1]['name']);
        $this->assertEquals('Zebra Product', $data[2]['name']);
    }

    #[Test]
    public function it_sorts_by_name_descending()
    {
        Product::factory()->create(['name' => 'Alpha Product']);
        Product::factory()->create(['name' => 'Beta Product']);
        Product::factory()->create(['name' => 'Zebra Product']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?sort=name&dir=desc');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertEquals('Zebra Product', $data[0]['name']);
        $this->assertEquals('Beta Product', $data[1]['name']);
        $this->assertEquals('Alpha Product', $data[2]['name']);
    }

    #[Test]
    public function it_sorts_by_price_ascending()
    {
        Product::factory()->create(['price' => 300.00]);
        Product::factory()->create(['price' => 100.00]);
        Product::factory()->create(['price' => 200.00]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?sort=price&dir=asc');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertEquals(100.00, $data[0]['price']);
        $this->assertEquals(200.00, $data[1]['price']);
        $this->assertEquals(300.00, $data[2]['price']);
    }

    #[Test]
    public function it_sorts_by_created_at_descending()
    {
        $product1 = Product::factory()->create(['created_at' => now()->subDays(3)]);
        $product2 = Product::factory()->create(['created_at' => now()->subDays(1)]);
        $product3 = Product::factory()->create(['created_at' => now()->subDays(2)]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?sort=created_at&dir=desc');

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertEquals($product2->id, $data[0]['id']); // Most recent
        $this->assertEquals($product3->id, $data[1]['id']); // Middle
        $this->assertEquals($product1->id, $data[2]['id']); // Oldest
    }

    #[Test]
    public function it_defaults_to_asc_when_dir_not_specified()
    {
        Product::factory()->create(['name' => 'Zebra Product']);
        Product::factory()->create(['name' => 'Alpha Product']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?sort=name');

        $response->assertStatus(200);
        
        $sort = $response->json('sort');
        $this->assertEquals('asc', $sort['dir']);
        
        $data = $response->json('data');
        $this->assertEquals('Alpha Product', $data[0]['name']);
    }

    #[Test]
    public function it_returns_null_sort_when_no_sort_parameters()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        
        // According to spec, sort should be null when no sorting is applied
        $this->assertNull($response->json('sort'));
    }

    #[Test]
    public function it_handles_invalid_sort_column()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?sort=invalid_column');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_PARAMETERS'
                ]
            ]);
    }

    #[Test]
    public function it_handles_invalid_sort_direction()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?sort=name&dir=invalid');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_PARAMETERS'
                ]
            ]);
    }

    #[Test]
    public function it_only_allows_sorting_on_sortable_columns()
    {
        Product::factory()->count(3)->create();

        // Assuming 'description' is not a sortable column based on getIndexColumns()
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?sort=description');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_PARAMETERS'
                ]
            ]);
    }
}
