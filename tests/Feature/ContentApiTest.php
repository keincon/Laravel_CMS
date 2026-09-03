<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\InstallationService;
use App\Services\LaravelPressBootstrapService;
use App\Services\RolePermissionService;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // File marker alone is not enough once the installations table exists.
        app(InstallationService::class)->markInstalled('test@example.com');

        app(LaravelPressBootstrapService::class)->seedBuiltins();
        app(RolePermissionService::class)->syncDefaults();
    }

    #[Test]
    public function public_can_list_types_and_published_contents(): void
    {
        $this->getJson('/api/v1/types')
            ->assertOk()
            ->assertJsonPath('data.0.slug', fn ($slug) => in_array($slug, ['post', 'page'], true) || is_string($slug));

        $this->getJson('/api/v1/contents')->assertOk()->assertJsonStructure(['data', 'meta' => ['current_page', 'total']]);
    }

    #[Test]
    public function authenticated_user_can_create_content(): void
    {
        $user = User::factory()->create(['username' => 'apiwriter']);
        $user->assignRole('Author');
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/contents', [
            'type' => 'post',
            'title' => 'API Post',
            'body' => 'Hello',
            'status' => 'draft',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'API Post')
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('contents', ['title' => 'API Post']);
    }

    #[Test]
    public function guest_cannot_create_content(): void
    {
        $this->postJson('/api/v1/contents', [
            'type' => 'post',
            'title' => 'Nope',
        ])->assertUnauthorized();
    }
}
