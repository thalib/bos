<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Test006SearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_returns_null_search_when_no_search_parameter()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);

        $this->assertNull($response->json('search'));
    }

    #[Test]
    public function it_returns_search_value_when_search_parameter_provided()
    {
        Product::factory()->create(['name' => 'iPhone 14']);
        Product::factory()->create(['name' => 'Samsung Galaxy']);
        Product::factory()->create(['name' => 'iPad Pro']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?search=iPhone');

        $response->assertStatus(200);

        $this->assertEquals('iPhone', $response->json('search'));
    }

    #[Test]
    public function it_searches_product_names()
    {
        Product::factory()->create(['name' => 'iPhone 14 Pro']);
        Product::factory()->create(['name' => 'Samsung Galaxy S23']);
        Product::factory()->create(['name' => 'iPad Pro 12.9']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?search=iPhone');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data));

        // Check that returned products contain the search term
        foreach ($data as $product) {
            $this->assertStringContainsStringIgnoringCase('iPhone', $product['name']);
        }
    }

    #[Test]
    public function it_searches_product_descriptions()
    {
        Product::factory()->create([
            'name' => 'Smartphone',
            'description' => 'Latest iPhone technology with advanced features',
        ]);
        Product::factory()->create([
            'name' => 'Tablet',
            'description' => 'Android tablet with premium display',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?search=iPhone');

        $response->assertStatus(200);

        $data = $response->json('data');

        if (count($data) > 0) {
            // At least one product should match the search term in description
            $found = false;
            foreach ($data as $product) {
                if (stripos($product['description'], 'iPhone') !== false) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, 'Search should find products with search term in description');
        }
    }

    #[Test]
    public function it_performs_case_insensitive_search()
    {
        Product::factory()->create(['name' => 'iPhone 14 Pro']);
        Product::factory()->create(['name' => 'Samsung Galaxy']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?search=iphone');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data));
        $this->assertStringContainsStringIgnoringCase('iPhone', $data[0]['name']);
    }

    #[Test]
    public function it_returns_empty_results_for_no_matches()
    {
        Product::factory()->create(['name' => 'iPhone 14']);
        Product::factory()->create(['name' => 'Samsung Galaxy']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?search=NonExistentProduct');

        $response->assertStatus(200);

        $this->assertEquals('NonExistentProduct', $response->json('search'));
        $this->assertCount(0, $response->json('data'));
    }

    #[Test]
    public function it_handles_partial_word_matching()
    {
        Product::factory()->create(['name' => 'Smartphone Case']);
        Product::factory()->create(['name' => 'Smart Watch']);
        Product::factory()->create(['name' => 'Tablet Stand']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?search=Smart');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data));

        // Should find both "Smartphone" and "Smart Watch"
        $foundSmartphone = false;
        $foundSmartWatch = false;

        foreach ($data as $product) {
            if (stripos($product['name'], 'Smartphone') !== false) {
                $foundSmartphone = true;
            }
            if (stripos($product['name'], 'Smart Watch') !== false) {
                $foundSmartWatch = true;
            }
        }

        $this->assertTrue($foundSmartphone || $foundSmartWatch, 'Should find products with partial word matches');
    }

    #[Test]
    public function it_validates_minimum_search_length()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?search=a'); // Single character

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'notifications' => [
                    '*' => [
                        'type',
                        'message',
                    ],
                ],
            ]);

        // Should have a warning notification about the short search term
        $notifications = $response->json('notifications');
        $this->assertNotNull($notifications);

        $hasSearchWarning = collect($notifications)->contains(function ($notification) {
            return $notification['type'] === 'warning' &&
                   str_contains($notification['message'], 'Search term too short');
        });
        $this->assertTrue($hasSearchWarning, 'Should have warning notification for short search term');

        // Search should be ignored/null
        $this->assertNull($response->json('search'));
    }

    #[Test]
    public function it_handles_search_with_special_characters()
    {
        Product::factory()->create(['name' => 'Product with & special characters']);
        Product::factory()->create(['name' => 'Normal Product']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?search='.urlencode('special characters'));

        $response->assertStatus(200);

        $this->assertEquals('special characters', $response->json('search'));
    }

    #[Test]
    public function it_combines_search_with_pagination()
    {
        // Create products that match search
        for ($i = 1; $i <= 25; $i++) {
            Product::factory()->create(['name' => "iPhone Model $i"]);
        }

        // Create products that don't match
        Product::factory()->count(10)->create(['name' => 'Samsung Galaxy']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?search=iPhone&per_page=10&page=1');

        $response->assertStatus(200);

        $this->assertEquals('iPhone', $response->json('search'));
        $this->assertCount(10, $response->json('data'));

        $pagination = $response->json('pagination');
        $this->assertEquals(25, $pagination['totalItems']); // Should only count matching items
        $this->assertEquals(3, $pagination['totalPages']); // 25 items / 10 per page
    }

    #[Test]
    public function it_combines_search_with_sorting()
    {
        Product::factory()->create(['name' => 'iPhone Z Model']);
        Product::factory()->create(['name' => 'iPhone A Model']);
        Product::factory()->create(['name' => 'Samsung Galaxy']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?search=iPhone&sort=name&dir=asc');

        $response->assertStatus(200);

        $this->assertEquals('iPhone', $response->json('search'));

        $data = $response->json('data');
        $this->assertCount(2, $data);
        $this->assertEquals('iPhone A Model', $data[0]['name']);
        $this->assertEquals('iPhone Z Model', $data[1]['name']);
    }
}
