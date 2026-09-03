<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Services\Content\LegacyContentMigrator;
use App\Services\Content\LegacyRetirementService;
use App\Services\InstallationService;
use App\Services\LaravelPressBootstrapService;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LegacyRetirementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(InstallationService::class)->markInstalled('test@example.com');
        app(LaravelPressBootstrapService::class)->seedBuiltins();
        app(RolePermissionService::class)->syncDefaults();
    }

    #[Test]
    public function retire_command_migrates_and_marks_retired(): void
    {
        $user = User::factory()->create(['username' => 'retiree']);
        Post::query()->create([
            'title' => 'To Retire',
            'slug' => 'to-retire',
            'content' => 'Body',
            'status' => 'publish',
            'author_id' => $user->id,
            'published_at' => now(),
        ]);

        $this->artisan('laravelpress:legacy', ['action' => 'retire', '--force' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('contents', ['slug' => 'to-retire']);
        $this->assertTrue(app(LegacyRetirementService::class)->isRetired());
        $this->assertFalse(app(LegacyRetirementService::class)->dualWriteEnabled());
        $this->assertFalse(app(LegacyRetirementService::class)->adminUiEnabled());
    }

    #[Test]
    public function legacy_admin_redirects_when_retired(): void
    {
        config(['cms.legacy.retired' => true, 'cms.legacy.admin_ui' => false]);
        app(LegacyRetirementService::class)->markRetired();

        $user = User::factory()->create(['username' => 'adminlegacy']);
        $user->assignRole('Administrator');

        $this->actingAs($user)
            ->get('/admin/posts?legacy=1')
            ->assertRedirect(route('admin.contents.index', ['type' => 'post']));
    }

    #[Test]
    public function dual_write_noops_when_retired_unless_forced(): void
    {
        config(['cms.legacy.retired' => true, 'cms.legacy.dual_write' => false]);
        app(LegacyRetirementService::class)->markRetired();

        $user = User::factory()->create(['username' => 'nowrite']);
        $post = Post::query()->create([
            'title' => 'No Sync',
            'slug' => 'no-sync',
            'content' => 'Body',
            'status' => 'publish',
            'author_id' => $user->id,
            'published_at' => now(),
        ]);

        $sync = app(\App\Services\Content\DualWriteContentSync::class);
        $this->assertNull($sync->syncPost($post));
        $this->assertNotNull($sync->syncPost($post, force: true));
        $this->assertDatabaseHas('contents', ['slug' => 'no-sync']);
    }

    #[Test]
    public function migrator_still_works_after_retirement(): void
    {
        config(['cms.legacy.retired' => true, 'cms.legacy.dual_write' => false]);
        $user = User::factory()->create(['username' => 'mig']);
        Post::query()->create([
            'title' => 'Forced Mig',
            'slug' => 'forced-mig',
            'content' => 'Body',
            'status' => 'publish',
            'author_id' => $user->id,
            'published_at' => now(),
        ]);

        $result = app(LegacyContentMigrator::class)->migrateAll();
        $this->assertSame(1, $result['posts']);
        $this->assertDatabaseHas('contents', ['slug' => 'forced-mig']);
    }
}
