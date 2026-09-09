<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\MailSettingsService;
use App\Services\MailTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MailServicesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function mail_settings_apply_ssl_scheme_and_defaults(): void
    {
        $mail = app(MailSettingsService::class);

        $mail->update([
            'mailer' => 'smtp',
            'host' => 'secure.example.com',
            'port' => 465,
            'encryption' => 'ssl',
            'username' => 'user',
            'password' => 'pw',
            'from_address' => 'from@example.com',
            'from_name' => 'From Name',
            'reply_to' => '',
        ]);

        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
        $this->assertSame(465, config('mail.mailers.smtp.port'));
        $this->assertSame('pw', $mail->decryptedPassword());
    }

    #[Test]
    public function clear_password_removes_stored_secret(): void
    {
        $mail = app(MailSettingsService::class);

        $mail->update([
            'mailer' => 'log',
            'host' => '127.0.0.1',
            'port' => 25,
            'encryption' => 'none',
            'username' => '',
            'password' => 'temp',
            'from_address' => 'from@example.com',
            'from_name' => 'From',
            'reply_to' => '',
        ]);
        $this->assertTrue($mail->current()['password_set']);

        $mail->update([
            'mailer' => 'log',
            'host' => '127.0.0.1',
            'port' => 25,
            'encryption' => 'none',
            'username' => '',
            'password' => '',
            'clear_password' => true,
            'from_address' => 'from@example.com',
            'from_name' => 'From',
            'reply_to' => '',
        ]);

        $this->assertFalse($mail->current()['password_set']);
        $this->assertSame('', $mail->decryptedPassword());
    }

    #[Test]
    public function template_render_merges_global_and_local_placeholders(): void
    {
        $templates = app(MailTemplateService::class);
        $templates->update([
            'test_email' => [
                'enabled' => true,
                'subject' => '{site_name} / {post_title}',
                'body' => '<p>{comment_author} @ {sent_at}</p>',
            ],
            'comment_notification' => [
                'enabled' => true,
                'subject' => 'c',
                'body' => 'c',
            ],
            'user_welcome' => [
                'enabled' => false,
                'subject' => 'u',
                'body' => 'u',
            ],
        ]);

        \App\Models\CmsSetting::setValue('site_name', 'Demo CMS');

        $rendered = $templates->render('test_email', [
            'post_title' => 'Hello',
            'comment_author' => 'Sam',
        ]);

        $this->assertSame('Demo CMS / Hello', $rendered['subject']);
        $this->assertStringContainsString('Sam', $rendered['body']);
        $this->assertStringContainsString('@', $rendered['body']);
    }

    #[Test]
    public function send_respects_enabled_flag_except_for_test_email(): void
    {
        Event::fake([MessageSent::class]);
        $templates = app(MailTemplateService::class);

        $templates->update([
            'test_email' => [
                'enabled' => false,
                'subject' => 'Test',
                'body' => '<p>Test</p>',
            ],
            'comment_notification' => [
                'enabled' => false,
                'subject' => 'Comment',
                'body' => '<p>Comment</p>',
            ],
            'user_welcome' => [
                'enabled' => false,
                'subject' => 'Welcome',
                'body' => '<p>Welcome</p>',
            ],
        ]);

        $this->assertTrue($templates->send('test_email', 'a@example.com'));
        Event::assertDispatched(MessageSent::class);

        Event::fake([MessageSent::class]);
        $this->assertFalse($templates->send('comment_notification', 'a@example.com'));
        Event::assertNotDispatched(MessageSent::class);
    }
}
