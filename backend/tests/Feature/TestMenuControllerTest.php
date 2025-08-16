<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TestMenuControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/v1/app/menu');

        $response->assertStatus(401)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertFalse($response->json('success'));
        $this->assertEquals('Authentication required', $response->json('message'));
    }

    #[Test]
    public function it_returns_successful_response_for_authenticated_user()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/app/menu');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotNull($response->json('message'));
        $this->assertIsArray($response->json('data'));
    }

    #[Test]
    public function it_returns_consistent_envelope_structure()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/app/menu');

        $response->assertStatus(200);

        // Verify envelope structure without asserting exact menu contents
        $responseData = $response->json();

        $this->assertArrayHasKey('success', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('data', $responseData);

        $this->assertIsBool($responseData['success']);
        $this->assertIsString($responseData['message']);
        $this->assertIsArray($responseData['data']);
    }
}
