<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LayoutSetting;
use App\Models\ThemeSetting;
use App\Models\User;
use App\Services\CustomCodeService;
use App\Services\InstallationService;
use App\Services\LaravelPressBootstrapService;
use App\Services\RolePermissionService;
use App\Services\ThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AppearanceSettingsTest extends TestCase
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
    public function admin_can_view_layout_colors_and_custom_code_in_japanese(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->withSession(['locale' => 'ja'])
            ->get(route('admin.appearance.layout'))
            ->assertOk()
            ->assertSee(__('admin.appearance.layout_intro', [], 'ja'), false)
            ->assertSee(__('admin.appearance.layout_save', [], 'ja'), false);

        $this->actingAs($admin)
            ->withSession(['locale' => 'ja'])
            ->get(route('admin.appearance.colors'))
            ->assertOk()
            ->assertSee(__('admin.appearance.colors_intro', [], 'ja'), false)
            ->assertSee(__('admin.appearance.colors_reset', [], 'ja'), false);

        $this->actingAs($admin)
            ->withSession(['locale' => 'ja'])
            ->get(route('admin.appearance.custom-code'))
            ->assertOk()
            ->assertSee(__('admin.appearance.custom_code_intro', [], 'ja'), false)
            ->assertSee(__('admin.appearance.custom_code_tab_css', [], 'ja'), false);
    }

    #[Test]
    public function admin_can_update_master_layout(): void
    {
        $admin = $this->admin();
        $current = LayoutSetting::current();

        $this->actingAs($admin)
            ->put(route('admin.appearance.layout.update'), [
                'default_header_id' => $current->default_header_id,
                'default_footer_id' => $current->default_footer_id,
                'container_width' => 1200,
                'content_width' => 800,
                'sidebar_width' => 280,
                'page_layout' => 'full_width',
                'post_layout' => 'standard',
                'sidebar_position' => 'left',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        LayoutSetting::forgetCache();
        $fresh = LayoutSetting::current();
        $this->assertSame(1200, (int) $fresh->container_width);
        $this->assertSame(800, (int) $fresh->content_width);
        $this->assertSame('full_width', $fresh->page_layout);
        $this->assertSame('left', $fresh->sidebar_position);
    }

    #[Test]
    public function admin_can_update_and_reset_theme_colors(): void
    {
        $admin = $this->admin();
        $defaults = ThemeSetting::defaults();
        $payload = [];
        foreach ($defaults as $key => $value) {
            if (str_ends_with($key, '_color')) {
                $payload[$key] = '#112233';
            }
        }

        $this->actingAs($admin)
            ->put(route('admin.appearance.colors.update'), $payload)
            ->assertRedirect()
            ->assertSessionHas('success');

        ThemeSetting::forgetCache();
        $this->assertSame('#112233', app(ThemeService::class)->colors()['primary']);

        $this->actingAs($admin)
            ->post(route('admin.appearance.colors.reset'))
            ->assertRedirect()
            ->assertSessionHas('success');

        ThemeSetting::forgetCache();
        $this->assertSame($defaults['primary_color'], app(ThemeService::class)->colors()['primary']);
    }

    #[Test]
    public function admin_can_save_custom_code(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('admin.appearance.custom-code.update'), [
                'additional_css' => '.hero { color: red; }',
                'header_scripts' => '<script>window.__hdr=1</script>',
                'footer_scripts' => '<script>window.__ftr=1</script>',
                'custom_html_head' => '<meta name="x-test" content="1">',
                'custom_html_body_open' => '<!-- open -->',
                'custom_html_body_close' => '<!-- close -->',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $code = app(CustomCodeService::class);
        $this->assertSame('.hero { color: red; }', $code->additionalCss());
        $this->assertStringContainsString('window.__hdr=1', $code->headerScripts());
        $this->assertStringContainsString('x-test', $code->customHtmlHead());
    }

    #[Test]
    public function author_cannot_access_appearance_settings(): void
    {
        $author = User::factory()->create(['username' => 'appearance-author']);
        $author->assignRole('Author');

        $this->actingAs($author)
            ->get(route('admin.appearance.layout'))
            ->assertForbidden();

        $this->actingAs($author)
            ->get(route('admin.appearance.colors'))
            ->assertForbidden();

        $this->actingAs($author)
            ->get(route('admin.appearance.custom-code'))
            ->assertForbidden();
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['username' => 'appearance-admin']);
        $admin->assignRole('Administrator');

        return $admin;
    }
}
