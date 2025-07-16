<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TestIndex003PaginationTest extends TestCase
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
                ],
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
    public function it_enforces_max_per_page_limit_with_notification()
    {
        Product::factory()->count(10)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?per_page=150'); // Over the 100 limit

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

        // Should have a warning notification about the invalid per_page value
        $notifications = $response->json('notifications');
        $this->assertNotNull($notifications);
        $this->assertIsArray($notifications);

        $hasPerPageWarning = collect($notifications)->contains(function ($notification) {
            return $notification['type'] === 'warning' &&
                   str_contains($notification['message'], 'exceeds maximum');
        });
        $this->assertTrue($hasPerPageWarning, 'Should have warning notification for per_page exceeding maximum');

        // Should fall back to maximum (100) instead of default
        $pagination = $response->json('pagination');
        $this->assertEquals(100, $pagination['itemsPerPage']); // Maximum allowed
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
    public function it_handles_invalid_page_parameter_with_notification()
    {
        Product::factory()->count(10)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?page=0');

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

        // Should have a warning notification about the invalid page value
        $notifications = $response->json('notifications');
        $this->assertNotNull($notifications);

        $hasPageWarning = collect($notifications)->contains(function ($notification) {
            return $notification['type'] === 'warning' &&
                   str_contains($notification['message'], 'Invalid page number');
        });
        $this->assertTrue($hasPageWarning, 'Should have warning notification for invalid page number');

        // Should fall back to page 1
        $pagination = $response->json('pagination');
        $this->assertEquals(1, $pagination['currentPage']);
    }

    #[Test]
    public function it_handles_invalid_per_page_parameter_with_notification()
    {
        Product::factory()->count(10)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?per_page=0');

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

        // Should have a warning notification about the invalid per_page value
        $notifications = $response->json('notifications');
        $this->assertNotNull($notifications);

        $hasPerPageWarning = collect($notifications)->contains(function ($notification) {
            return $notification['type'] === 'warning' &&
                   str_contains($notification['message'], 'Page size');
        });
        $this->assertTrue($hasPerPageWarning, 'Should have warning notification for invalid per_page');

        // Should fall back to minimum (1) instead of default
        $pagination = $response->json('pagination');
        $this->assertEquals(1, $pagination['itemsPerPage']); // Minimum allowed
    }

    #[Test]
    public function it_returns_last_page_for_page_beyond_total_with_notification()
    {
        Product::factory()->count(5)->create(); // 5 products with per_page=10 means only 1 page

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?page=10&per_page=10'); // Requesting page 10 when only 1 page exists

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

        // Should have a warning notification about page exceeding available pages
        $notifications = $response->json('notifications');
        $this->assertNotNull($notifications);

        $hasPageWarning = collect($notifications)->contains(function ($notification) {
            return $notification['type'] === 'warning' &&
                   str_contains($notification['message'], 'exceeds available pages');
        });
        $this->assertTrue($hasPageWarning, 'Should have warning notification for page exceeding available pages');

        // Should return data for the last available page (page 1)
        $this->assertCount(5, $response->json('data')); // All 5 products on the last page
        $pagination = $response->json('pagination');
        $this->assertEquals(1, $pagination['currentPage']); // Should be set to last available page
        $this->assertEquals(5, $pagination['totalItems']);
        $this->assertEquals(1, $pagination['totalPages']);
    }
}
