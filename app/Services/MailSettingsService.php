<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\SettingsUpdated;
use App\Models\CmsSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Admin-managed mail transport stored in CmsSetting and applied to config('mail').
 */
final class MailSettingsService
{
    public const SETTING_KEY = 'mail';

    /**
     * @return array{
     *     mailer: string,
     *     host: string,
     *     port: int,
     *     encryption: string,
     *     username: string,
     *     password: string,
     *     from_address: string,
     *     from_name: string,
     *     reply_to: string
     * }
     */
    public function defaults(): array
    {
        return [
            'mailer' => (string) config('mail.default', 'log'),
            'host' => (string) config('mail.mailers.smtp.host', '127.0.0.1'),
            'port' => (int) config('mail.mailers.smtp.port', 2525),
            'encryption' => 'none',
            'username' => (string) (config('mail.mailers.smtp.username') ?? ''),
            'password' => '',
            'from_address' => (string) config('mail.from.address', 'hello@example.com'),
            'from_name' => (string) config('mail.from.name', config('app.name')),
            'reply_to' => '',
        ];
    }

    /**
     * @return array{
     *     mailer: string,
     *     host: string,
     *     port: int,
     *     encryption: string,
     *     username: string,
     *     password: string,
     *     password_set: bool,
     *     from_address: string,
     *     from_name: string,
     *     reply_to: string
     * }
     */
    public function current(): array
    {
        $defaults = $this->defaults();
        $stored = CmsSetting::getValue(self::SETTING_KEY, null);
        if (! is_array($stored)) {
            return array_merge($defaults, ['password' => '', 'password_set' => false]);
        }

        $passwordEncrypted = (string) ($stored['password_encrypted'] ?? '');
        $passwordSet = $passwordEncrypted !== '';

        return [
            'mailer' => $this->normalizeMailer((string) ($stored['mailer'] ?? $defaults['mailer'])),
            'host' => (string) ($stored['host'] ?? $defaults['host']),
            'port' => max(1, min(65535, (int) ($stored['port'] ?? $defaults['port']))),
            'encryption' => $this->normalizeEncryption((string) ($stored['encryption'] ?? 'none')),
            'username' => (string) ($stored['username'] ?? ''),
            'password' => '',
            'password_set' => $passwordSet,
            'from_address' => (string) ($stored['from_address'] ?? $defaults['from_address']),
            'from_name' => (string) ($stored['from_name'] ?? $defaults['from_name']),
            'reply_to' => (string) ($stored['reply_to'] ?? ''),
            'password_encrypted' => $passwordEncrypted,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function update(array $input): array
    {
        $current = $this->current();
        $mailer = $this->normalizeMailer((string) ($input['mailer'] ?? $current['mailer']));
        $encryption = $this->normalizeEncryption((string) ($input['encryption'] ?? 'none'));

        $settings = [
            'mailer' => $mailer,
            'host' => trim((string) ($input['host'] ?? $current['host'])),
            'port' => max(1, min(65535, (int) ($input['port'] ?? $current['port']))),
            'encryption' => $encryption,
            'username' => trim((string) ($input['username'] ?? '')),
            'from_address' => trim((string) ($input['from_address'] ?? '')),
            'from_name' => trim((string) ($input['from_name'] ?? '')),
            'reply_to' => trim((string) ($input['reply_to'] ?? '')),
            'password_encrypted' => $current['password_encrypted'] ?? '',
        ];

        $newPassword = (string) ($input['password'] ?? '');
        if ($newPassword !== '') {
            $settings['password_encrypted'] = Crypt::encryptString($newPassword);
        }
        if (! empty($input['clear_password'])) {
            $settings['password_encrypted'] = '';
        }

        if ($settings['from_address'] === '' || ! filter_var($settings['from_address'], FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'from_address' => __('admin.settings.mail_invalid_from'),
            ]);
        }

        if ($settings['reply_to'] !== '' && ! filter_var($settings['reply_to'], FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'reply_to' => __('admin.settings.mail_invalid_reply_to'),
            ]);
        }

        if ($mailer === 'smtp' && $settings['host'] === '') {
            throw ValidationException::withMessages([
                'host' => __('admin.settings.mail_host_required'),
            ]);
        }

        CmsSetting::setValue(self::SETTING_KEY, $settings, 'json');
        $this->applyToConfig($this->current());
        event(new SettingsUpdated(self::SETTING_KEY));

        return $this->current();
    }

    public function applyFromDatabase(): void
    {
        try {
            if (! Schema::hasTable('cms_settings')) {
                return;
            }
            $this->applyToConfig($this->current());
        } catch (\Throwable) {
            // Install / early boot without DB.
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function applyToConfig(array $settings): void
    {
        $mailer = $this->normalizeMailer((string) ($settings['mailer'] ?? 'log'));
        $encryption = $this->normalizeEncryption((string) ($settings['encryption'] ?? 'none'));
        $password = $this->decryptPassword((string) ($settings['password_encrypted'] ?? ''));

        $scheme = match ($encryption) {
            'ssl' => 'smtps',
            default => null,
        };

        config([
            'mail.default' => $mailer,
            'mail.from.address' => (string) ($settings['from_address'] ?? config('mail.from.address')),
            'mail.from.name' => (string) ($settings['from_name'] ?? config('mail.from.name')),
            'mail.mailers.smtp.host' => (string) ($settings['host'] ?? '127.0.0.1'),
            'mail.mailers.smtp.port' => (int) ($settings['port'] ?? 2525),
            'mail.mailers.smtp.username' => (string) ($settings['username'] ?? '') ?: null,
            'mail.mailers.smtp.password' => $password !== '' ? $password : null,
            'mail.mailers.smtp.scheme' => $scheme,
        ]);
    }

    public function decryptedPassword(): string
    {
        $current = $this->current();

        return $this->decryptPassword((string) ($current['password_encrypted'] ?? ''));
    }

    /**
     * @return list<string>
     */
    public function mailerOptions(): array
    {
        return ['smtp', 'log', 'sendmail', 'array'];
    }

    /**
     * @return list<string>
     */
    public function encryptionOptions(): array
    {
        return ['none', 'tls', 'ssl'];
    }

    private function normalizeMailer(string $mailer): string
    {
        $mailer = strtolower(trim($mailer));

        return in_array($mailer, $this->mailerOptions(), true) ? $mailer : 'log';
    }

    private function normalizeEncryption(string $encryption): string
    {
        $encryption = strtolower(trim($encryption));

        return in_array($encryption, $this->encryptionOptions(), true) ? $encryption : 'none';
    }

    private function decryptPassword(string $encrypted): string
    {
        if ($encrypted === '') {
            return '';
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            return '';
        }
    }
}
