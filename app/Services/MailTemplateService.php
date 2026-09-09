<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\SettingsUpdated;
use App\Models\CmsSetting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * Editable notification email templates (subject + HTML/text body) with {placeholders}.
 */
final class MailTemplateService
{
    public const SETTING_KEY = 'mail_templates';

    public function __construct(
        private readonly MailSettingsService $mailSettings,
    ) {}

    /**
     * @return array<string, array{enabled: bool, subject: string, body: string, description: string}>
     */
    public function defaults(): array
    {
        return [
            'test_email' => [
                'enabled' => true,
                'subject' => '[{site_name}] Test email',
                'body' => "<p>Hello,</p>\n<p>This is a test message from <strong>{site_name}</strong>.</p>\n<p>If you received this, mail delivery is working.</p>\n<p>Sent at: {sent_at}</p>",
                'description' => 'Used by the “Send test email” button on Mail settings.',
            ],
            'comment_notification' => [
                'enabled' => true,
                'subject' => '[{site_name}] New comment on {post_title}',
                'body' => "<p>A new comment was submitted.</p>\n<p><strong>Post:</strong> {post_title}<br>\n<strong>Author:</strong> {comment_author}<br>\n<strong>Email:</strong> {comment_email}</p>\n<blockquote>{comment_content}</blockquote>\n<p><a href=\"{comment_moderate_url}\">Moderate in admin</a></p>",
                'description' => 'Sent to the site admin when a new comment needs attention (if enabled).',
            ],
            'user_welcome' => [
                'enabled' => false,
                'subject' => 'Welcome to {site_name}',
                'body' => "<p>Hello {user_name},</p>\n<p>Your account on <strong>{site_name}</strong> is ready.</p>\n<p>Username: {user_username}<br>\nLogin: <a href=\"{login_url}\">{login_url}</a></p>",
                'description' => 'Optional welcome mail when an administrator creates a user (call from code / future hooks).',
            ],
        ];
    }

    /**
     * @return array<string, array{enabled: bool, subject: string, body: string, description: string}>
     */
    public function all(): array
    {
        $defaults = $this->defaults();
        $stored = [];
        try {
            if (Schema::hasTable('cms_settings')) {
                $value = CmsSetting::getValue(self::SETTING_KEY, null);
                $stored = is_array($value) ? $value : [];
            }
        } catch (\Throwable) {
            $stored = [];
        }

        $out = [];
        foreach ($defaults as $key => $default) {
            $row = is_array($stored[$key] ?? null) ? $stored[$key] : [];
            $out[$key] = [
                'enabled' => array_key_exists('enabled', $row) ? (bool) $row['enabled'] : (bool) $default['enabled'],
                'subject' => (string) ($row['subject'] ?? $default['subject']),
                'body' => (string) ($row['body'] ?? $default['body']),
                'description' => (string) $default['description'],
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, array{enabled?: mixed, subject?: mixed, body?: mixed}>  $input
     * @return array<string, array{enabled: bool, subject: string, body: string, description: string}>
     */
    public function update(array $input): array
    {
        $defaults = $this->defaults();
        $payload = [];

        foreach ($defaults as $key => $default) {
            $row = is_array($input[$key] ?? null) ? $input[$key] : [];
            $payload[$key] = [
                'enabled' => (bool) ($row['enabled'] ?? false),
                'subject' => mb_substr(trim((string) ($row['subject'] ?? $default['subject'])), 0, 255),
                'body' => mb_substr((string) ($row['body'] ?? $default['body']), 0, 20000),
            ];
        }

        CmsSetting::setValue(self::SETTING_KEY, $payload, 'json');
        event(new SettingsUpdated(self::SETTING_KEY));

        return $this->all();
    }

    /**
     * @param  array<string, string|int|float|null>  $vars
     * @return array{subject: string, body: string, enabled: bool}
     */
    public function render(string $key, array $vars = []): array
    {
        $all = $this->all();
        $template = $all[$key] ?? null;
        if ($template === null) {
            return ['subject' => '', 'body' => '', 'enabled' => false];
        }

        $vars = array_merge($this->globalVars(), $vars);
        $replace = [];
        foreach ($vars as $name => $value) {
            $replace['{'.$name.'}'] = (string) ($value ?? '');
        }

        return [
            'enabled' => (bool) $template['enabled'],
            'subject' => strtr($template['subject'], $replace),
            'body' => strtr($template['body'], $replace),
        ];
    }

    /**
     * @param  array<string, string|int|float|null>  $vars
     */
    public function send(string $key, string $to, array $vars = []): bool
    {
        $rendered = $this->render($key, $vars);
        if (! $rendered['enabled'] && $key !== 'test_email') {
            return false;
        }

        $this->mailSettings->applyFromDatabase();

        $replyTo = trim((string) ($this->mailSettings->current()['reply_to'] ?? ''));

        Mail::html($rendered['body'], function ($message) use ($to, $rendered, $replyTo) {
            $message->to($to)->subject($rendered['subject']);
            if ($replyTo !== '') {
                $message->replyTo($replyTo);
            }
        });

        return true;
    }

    /**
     * @return array<string, string>
     */
    public function globalVars(): array
    {
        return [
            'site_name' => (string) (CmsSetting::getValue('site_name') ?: config('cms.name', config('app.name'))),
            'site_url' => rtrim((string) (CmsSetting::getValue('site_url') ?: config('app.url')), '/'),
            'login_url' => url('/login'),
            'sent_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * @return list<string>
     */
    public function placeholderHints(): array
    {
        return [
            'site_name', 'site_url', 'login_url', 'sent_at',
            'post_title', 'comment_author', 'comment_email', 'comment_content', 'comment_moderate_url',
            'user_name', 'user_username',
        ];
    }
}
