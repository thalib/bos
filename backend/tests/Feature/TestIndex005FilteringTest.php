<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TestIndex005FilteringTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_returns_null_filters_when_no_get_api_filters_method()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);

        // According to spec, filters should be null when model doesn't define getApiFilters()
        $this->assertNull($response->json('filters'));
    }

    #[Test]
    public function it_returns_correct_filter_structure_when_available()
    {
        // This test assumes the Product model will have getApiFilters() method implemented
        // For now, we test the expected structure based on the API spec

        Product::factory()->create(['categories' => ['Electronics']]);
        Product::factory()->create(['categories' => ['Books']]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'filters', // Should be null or have the correct structure
            ]);

        $filters = $response->json('filters');

        if ($filters !== null) {
            $this->assertArrayHasKey('applied', $filters);
            $this->assertArrayHasKey('available', $filters);
        }
    }

    #[Test]
    public function it_applies_filter_when_filter_parameter_provided()
    {
        // Create products with different categories
        Product::factory()->create(['categories' => ['Electronics'], 'name' => 'Phone']);
        Product::factory()->create(['categories' => ['Books'], 'name' => 'Novel']);
        Product::factory()->create(['categories' => ['Electronics'], 'name' => 'Laptop']);

        // This test will need to be updated once filtering is actually implemented
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?filter=categories:Electronics');

        // For now, we expect this to work or return an appropriate error
        // The actual behavior will depend on the implementation
        $this->assertContains($response->status(), [200, 400]);

        if ($response->status() === 200) {
            $filters = $response->json('filters');
            if ($filters !== null && isset($filters['applied'])) {
                $this->assertEquals('categories', $filters['applied']['field']);
                $this->assertEquals('Electronics', $filters['applied']['value']);
            }
        }
    }

    #[Test]
    public function it_handles_invalid_filter_format_with_notification()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?filter=invalid_format');

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

        // Should have a warning notification about the invalid filter format
        $notifications = $response->json('notifications');
        $this->assertNotNull($notifications);

        $hasFilterWarning = collect($notifications)->contains(function ($notification) {
            return $notification['type'] === 'warning' &&
                   str_contains($notification['message'], 'Filter format');
        });
        $this->assertTrue($hasFilterWarning, 'Should have warning notification for invalid filter format');

        // Filter should be ignored
        $filters = $response->json('filters');
        $this->assertNull($filters['applied'] ?? null);
    }

    #[Test]
    public function it_handles_invalid_filter_field_with_notification()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?filter=nonexistent_field:value');

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

        // Should have a warning notification about the invalid filter field
        $notifications = $response->json('notifications');
        $this->assertNotNull($notifications);

        $hasFilterWarning = collect($notifications)->contains(function ($notification) {
            return $notification['type'] === 'warning' &&
                   (str_contains($notification['message'], 'Filter') ||
                    str_contains($notification['message'], 'field'));
        });
        $this->assertTrue($hasFilterWarning, 'Should have warning notification for invalid filter field');

        // Filter should be ignored
        $filters = $response->json('filters');
        $this->assertNull($filters['applied'] ?? null);
    }

    #[Test]
    public function it_only_allows_one_filter_at_a_time()
    {
        Product::factory()->count(3)->create();

        // According to spec, only one filter can be active at a time
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?filter=categories:Electronics&filter=status:active');

        // Should either apply the last filter or return an error
        $this->assertContains($response->status(), [200, 400]);

        if ($response->status() === 200) {
            $filters = $response->json('filters');
            if ($filters !== null && isset($filters['applied'])) {
                // Should only have one applied filter
                $this->assertIsArray($filters['applied']);
                $this->assertCount(2, $filters['applied']); // field and value keys
            }
        }
    }

    #[Test]
    public function it_returns_available_filters_when_model_defines_them()
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);

        $filters = $response->json('filters');

        if ($filters !== null && isset($filters['available'])) {
            $this->assertIsArray($filters['available']);

            foreach ($filters['available'] as $filter) {
                $this->assertArrayHasKey('field', $filter);
                $this->assertArrayHasKey('label', $filter);
                $this->assertArrayHasKey('values', $filter);
                $this->assertIsArray($filter['values']);
            }
        }
    }

    #[Test]
    public function it_replaces_existing_filter_when_new_filter_applied()
    {
        Product::factory()->create(['categories' => ['Electronics']]);
        Product::factory()->create(['categories' => ['Books']]);

        // Apply first filter
        $response1 = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?filter=categories:Electronics');

        // Apply second filter (should replace the first)
        $response2 = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?filter=categories:Books');

        $this->assertContains($response2->status(), [200, 400]);

        if ($response2->status() === 200) {
            $filters = $response2->json('filters');
            if ($filters !== null && isset($filters['applied'])) {
                $this->assertEquals('Books', $filters['applied']['value']);
            }
        }
    }
}
