<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CmsSetting;
use App\Models\Content;
use App\Models\Page;
use App\Models\User;
use App\Services\Content\ContentService;
use App\Services\DynamicPageService;
use App\Services\InstallationService;
use App\Services\LaravelPressBootstrapService;
use App\Services\MailSettingsService;
use App\Services\MailTemplateService;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LocaleAndAdminBarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(InstallationService::class)->markInstalled('test@example.com');
        app(LaravelPressBootstrapService::class)->seedBuiltins();
        app(RolePermissionService::class)->syncDefaults();
        Cache::flush();
        app(DynamicPageService::class)->ensureDefaults();
    }

    #[Test]
    public function session_locale_is_applied_by_middleware(): void
    {
        $this->withSession(['locale' => 'ja'])
            ->get(route('admin.dashboard'))
            ->assertRedirect(); // guest redirected to login, but locale already set during request

        // Authenticated request so we can assert translated chrome.
        $admin = User::factory()->create(['username' => 'locale-admin', 'locale' => 'en']);
        $admin->assignRole('Administrator');

        $this->actingAs($admin)
            ->withSession(['locale' => 'ja'])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('ダッシュボード', false);

        $this->assertSame('ja', App::getLocale());
    }

    #[Test]
    public function locale_switcher_updates_session_and_user(): void
    {
        $admin = User::factory()->create(['username' => 'locale-switch', 'locale' => 'en']);
        $admin->assignRole('Administrator');

        $this->actingAs($admin)
            ->from(route('admin.dashboard'))
            ->post(route('locale.update'), ['locale' => 'ja'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame('ja', session('locale'));
        $this->assertSame('ja', $admin->fresh()->locale);
    }

    #[Test]
    public function site_language_setting_is_used_when_no_session_or_user_locale(): void
    {
        CmsSetting::setValue('language', 'ja');

        $admin = User::factory()->create([
            'username' => 'site-lang-admin',
            'locale' => null,
        ]);
        $admin->assignRole('Administrator');

        $this->actingAs($admin)
            ->withSession([])
            ->get(route('admin.dashboard'))
            ->assertOk();

        $this->assertSame('ja', App::getLocale());
    }

    #[Test]
    public function public_admin_bar_labels_follow_locale(): void
    {
        $admin = User::factory()->create(['username' => 'bar-admin', 'locale' => 'ja']);
        $admin->assignRole('Administrator');

        Page::query()->create([
            'title' => 'About Locale',
            'slug' => 'about-locale',
            'content' => '<p>Hello</p>',
            'status' => 'publish',
            'author_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->withSession(['locale' => 'ja'])
            ->get('/about-locale')
            ->assertOk()
            ->assertSee(__('admin.admin_bar.dashboard', [], 'ja'), false)
            ->assertSee(__('admin.admin_bar.new_post', [], 'ja'), false)
            ->assertSee(__('admin.admin_bar.log_out', [], 'ja'), false)
            ->assertDontSee('+ New Post', false);
    }

    #[Test]
    public function comment_notification_mail_is_sent_when_enabled(): void
    {
        Event::fake([MessageSent::class]);

        app(MailSettingsService::class)->update([
            'mailer' => 'array',
            'host' => '127.0.0.1',
            'port' => 2525,
            'encryption' => 'none',
            'username' => '',
            'password' => '',
            'from_address' => 'admin-notify@example.com',
            'from_name' => 'Notify',
            'reply_to' => '',
        ]);

        app(MailTemplateService::class)->update([
            'test_email' => ['enabled' => true, 'subject' => 't', 'body' => 't'],
            'comment_notification' => [
                'enabled' => true,
                'subject' => 'New on {post_title}',
                'body' => '<p>{comment_author}</p>',
            ],
            'user_welcome' => ['enabled' => false, 'subject' => 'w', 'body' => 'w'],
        ]);

        CmsSetting::setValue('comments_enabled', '1', 'boolean');
        CmsSetting::setValue('comment_moderation', '0', 'boolean');

        $author = User::factory()->create(['username' => 'comment-mail-author']);
        $author->assignRole('Author');

        $content = app(ContentService::class)->create('post', [
            'title' => 'Notify Me',
            'slug' => 'notify-me-post',
            'body' => '<p>Body</p>',
            'status' => 'published',
            'published_at' => now(),
            'comment_status' => 'open',
        ], $author);

        $this->assertInstanceOf(Content::class, $content);

        $this->post(route('comments.store.content', $content), [
            'author_name' => 'Guest',
            'author_email' => 'guest@example.com',
            'content' => 'Nice post',
        ])->assertRedirect();

        Event::assertDispatched(MessageSent::class);
    }

    #[Test]
    public function disabled_comment_template_does_not_send_mail(): void
    {
        Event::fake([MessageSent::class]);

        app(MailSettingsService::class)->update([
            'mailer' => 'array',
            'host' => '127.0.0.1',
            'port' => 2525,
            'encryption' => 'none',
            'username' => '',
            'password' => '',
            'from_address' => 'admin-notify@example.com',
            'from_name' => 'Notify',
            'reply_to' => '',
        ]);

        app(MailTemplateService::class)->update([
            'test_email' => ['enabled' => true, 'subject' => 't', 'body' => 't'],
            'comment_notification' => [
                'enabled' => false,
                'subject' => 'New',
                'body' => '<p>x</p>',
            ],
            'user_welcome' => ['enabled' => false, 'subject' => 'w', 'body' => 'w'],
        ]);

        CmsSetting::setValue('comments_enabled', '1', 'boolean');
        CmsSetting::setValue('comment_moderation', '0', 'boolean');

        $author = User::factory()->create(['username' => 'comment-mail-off']);
        $author->assignRole('Author');

        $content = app(ContentService::class)->create('post', [
            'title' => 'Silent',
            'slug' => 'silent-post',
            'body' => '<p>Body</p>',
            'status' => 'published',
            'published_at' => now(),
            'comment_status' => 'open',
        ], $author);

        $this->post(route('comments.store.content', $content), [
            'author_name' => 'Guest',
            'author_email' => 'guest@example.com',
            'content' => 'Hi',
        ])->assertRedirect();

        Event::assertNotDispatched(MessageSent::class);
    }
}
