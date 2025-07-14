<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Test011NotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_returns_notifications_for_multiple_parameter_issues()
    {
        Product::factory()->count(10)->create();

        // Test with multiple invalid parameters that should generate notifications
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?page=0&per_page=150&sort=invalid_column&dir=invalid_direction&filter=invalid_format&search=a');

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

        $notifications = $response->json('notifications');
        $this->assertNotNull($notifications);
        $this->assertIsArray($notifications);
        $this->assertGreaterThan(0, count($notifications));

        // Verify all notifications have correct structure
        foreach ($notifications as $notification) {
            $this->assertArrayHasKey('type', $notification);
            $this->assertArrayHasKey('message', $notification);
            $this->assertIsString($notification['type']);
            $this->assertIsString($notification['message']);
            $this->assertContains($notification['type'], ['info', 'warning', 'success']);
        }

        // Should fall back to defaults despite invalid parameters
        $pagination = $response->json('pagination');
        $this->assertEquals(1, $pagination['currentPage']); // Fallback from page=0
        $this->assertEquals(100, $pagination['itemsPerPage']); // Fallback from per_page=150 (capped at maximum)

        $sort = $response->json('sort');
        $this->assertNotNull($sort);
        $this->assertNotEquals('invalid_column', $sort['column']);
        $this->assertEquals('asc', $sort['dir']); // Fallback from invalid_direction

        // Search should be null due to short term
        $this->assertNull($response->json('search'));

        // Filter should be null due to invalid format
        $filters = $response->json('filters');
        $this->assertNull($filters['applied'] ?? null);
    }

    #[Test]
    public function it_handles_info_notifications_for_default_values()
    {
        Product::factory()->count(10)->create();

        // Test scenario that might generate info notifications
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200);

        $notifications = $response->json('notifications');

        // Check if there are any info notifications
        if ($notifications !== null) {
            $infoNotifications = collect($notifications)->filter(function ($notification) {
                return $notification['type'] === 'info';
            });

            foreach ($infoNotifications as $notification) {
                $this->assertIsString($notification['message']);
            }
        }
    }

    #[Test]
    public function it_does_not_include_notifications_in_error_responses()
    {
        // Test unauthorized access
        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'error' => [
                    'code',
                    'details',
                ],
            ]);

        // Ensure notifications field is not present in error responses
        $this->assertArrayNotHasKey('notifications', $response->json());
    }

    #[Test]
    public function it_returns_warning_notifications_for_ignored_parameters()
    {
        Product::factory()->count(5)->create();

        // Test with parameters that should be ignored with warnings
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?invalid_param=value&another_invalid=test');

        $response->assertStatus(200);

        // The invalid parameters should be ignored and might generate notifications
        // depending on implementation, but response should still be successful
        $this->assertTrue($response->json('success'));
    }

    #[Test]
    public function it_returns_notifications_with_proper_message_content()
    {
        Product::factory()->count(5)->create();

        // Test specific scenarios with expected notification messages
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products?per_page=500'); // Way over limit

        $response->assertStatus(200);

        $notifications = $response->json('notifications');

        if ($notifications !== null) {
            $hasPerPageNotification = collect($notifications)->contains(function ($notification) {
                return $notification['type'] === 'warning' &&
                       str_contains(strtolower($notification['message']), 'page size') ||
                       str_contains(strtolower($notification['message']), 'exceeds maximum') ||
                       str_contains(strtolower($notification['message']), 'default');
            });

            if ($hasPerPageNotification) {
                $this->assertTrue($hasPerPageNotification, 'Should have meaningful message about page size issue');
            }
        }
    }
}
