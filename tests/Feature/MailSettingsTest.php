<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\InstallationService;
use App\Services\LaravelPressBootstrapService;
use App\Services\MailSettingsService;
use App\Services\MailTemplateService;
use App\Services\RolePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MailSettingsTest extends TestCase
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
    public function admin_can_view_and_update_mail_settings(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.settings.mail'))
            ->assertOk()
            ->assertSee(__('admin.settings.mail_from_address'), false);

        $this->actingAs($admin)
            ->put(route('admin.settings.mail.update'), [
                'mailer' => 'smtp',
                'host' => 'smtp.example.com',
                'port' => 587,
                'encryption' => 'tls',
                'username' => 'mailer@example.com',
                'password' => 'secret-pass',
                'from_address' => 'noreply@example.com',
                'from_name' => 'LaravelPress Tests',
                'reply_to' => 'support@example.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $settings = app(MailSettingsService::class)->current();
        $this->assertSame('smtp', $settings['mailer']);
        $this->assertSame('smtp.example.com', $settings['host']);
        $this->assertSame(587, $settings['port']);
        $this->assertSame('tls', $settings['encryption']);
        $this->assertTrue($settings['password_set']);
        $this->assertSame('noreply@example.com', $settings['from_address']);
        $this->assertSame('support@example.com', $settings['reply_to']);

        app(MailSettingsService::class)->applyFromDatabase();
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertSame('noreply@example.com', config('mail.from.address'));
        $this->assertSame('secret-pass', config('mail.mailers.smtp.password'));
    }

    #[Test]
    public function blank_password_keeps_existing_secret(): void
    {
        $admin = $this->admin();
        $mail = app(MailSettingsService::class);

        $mail->update([
            'mailer' => 'smtp',
            'host' => 'smtp.example.com',
            'port' => 465,
            'encryption' => 'ssl',
            'username' => 'u',
            'password' => 'keep-me',
            'from_address' => 'from@example.com',
            'from_name' => 'From',
            'reply_to' => '',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.settings.mail.update'), [
                'mailer' => 'smtp',
                'host' => 'smtp.example.com',
                'port' => 465,
                'encryption' => 'ssl',
                'username' => 'u',
                'password' => '',
                'from_address' => 'from@example.com',
                'from_name' => 'From',
                'reply_to' => '',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $mail->applyFromDatabase();
        $this->assertSame('keep-me', config('mail.mailers.smtp.password'));
    }

    #[Test]
    public function smtp_requires_host_and_valid_from(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from(route('admin.settings.mail'))
            ->put(route('admin.settings.mail.update'), [
                'mailer' => 'smtp',
                'host' => '',
                'port' => 587,
                'encryption' => 'none',
                'username' => '',
                'password' => '',
                'from_address' => 'not-an-email',
                'from_name' => 'From',
                'reply_to' => '',
            ])
            ->assertRedirect(route('admin.settings.mail'))
            ->assertSessionHasErrors();
    }

    #[Test]
    public function admin_can_send_test_email(): void
    {
        Event::fake([MessageSent::class]);
        $admin = $this->admin();

        app(MailSettingsService::class)->update([
            'mailer' => 'array',
            'host' => '127.0.0.1',
            'port' => 2525,
            'encryption' => 'none',
            'username' => '',
            'password' => '',
            'from_address' => 'noreply@example.com',
            'from_name' => 'Tests',
            'reply_to' => '',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.settings.mail.test'), [
                'test_to' => 'recipient@example.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Event::assertDispatched(MessageSent::class);
    }

    #[Test]
    public function admin_can_update_mail_templates(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.settings.mail.templates'))
            ->assertOk()
            ->assertSee('test_email', false)
            ->assertSee(__('admin.settings.mail_template_types.test_email'), false);

        $this->actingAs($admin)
            ->put(route('admin.settings.mail.templates.update'), [
                'templates' => [
                    'test_email' => [
                        'enabled' => '1',
                        'subject' => '[{site_name}] Hello test',
                        'body' => '<p>Hi {site_name}</p>',
                    ],
                    'comment_notification' => [
                        'enabled' => '1',
                        'subject' => 'Comment on {post_title}',
                        'body' => '<p>{comment_author}</p>',
                    ],
                    'user_welcome' => [
                        'subject' => 'Welcome',
                        'body' => '<p>Hi {user_name}</p>',
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $templates = app(MailTemplateService::class)->all();
        $this->assertSame('[{site_name}] Hello test', $templates['test_email']['subject']);
        $this->assertTrue($templates['comment_notification']['enabled']);
        $this->assertFalse($templates['user_welcome']['enabled']);

        $rendered = app(MailTemplateService::class)->render('comment_notification', [
            'post_title' => 'Hello',
            'comment_author' => 'Ada',
        ]);
        $this->assertSame('Comment on Hello', $rendered['subject']);
        $this->assertStringContainsString('Ada', $rendered['body']);
    }

    #[Test]
    public function author_cannot_manage_mail_settings(): void
    {
        $author = User::factory()->create(['username' => 'mail-author']);
        $author->assignRole('Author');

        $this->actingAs($author)
            ->get(route('admin.settings.mail'))
            ->assertForbidden();
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['username' => 'mail-admin']);
        $admin->assignRole('Administrator');

        return $admin;
    }
}
