<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MailSettingsService;
use App\Services\MailTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MailSettingsController extends Controller
{
    public function edit(MailSettingsService $mail): View
    {
        $settings = $mail->current();

        return view('admin.settings.mail', [
            'settings' => collect($settings)->except('password_encrypted')->all(),
            'mailers' => $mail->mailerOptions(),
            'encryptions' => $mail->encryptionOptions(),
        ]);
    }

    public function update(Request $request, MailSettingsService $mail): RedirectResponse
    {
        $data = $request->validate([
            'mailer' => ['required', 'in:smtp,log,sendmail,array'],
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['required', 'in:none,tls,ssl'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:500'],
            'clear_password' => ['nullable', 'boolean'],
            'from_address' => ['required', 'email', 'max:255'],
            'from_name' => ['required', 'string', 'max:255'],
            'reply_to' => ['nullable', 'email', 'max:255'],
        ]);

        $mail->update([
            ...$data,
            'clear_password' => $request->boolean('clear_password'),
        ]);

        return back()->with('success', __('admin.settings.mail_saved'));
    }

    public function sendTest(Request $request, MailTemplateService $templates): RedirectResponse
    {
        $data = $request->validate([
            'test_to' => ['required', 'email', 'max:255'],
        ]);

        try {
            $templates->send('test_email', $data['test_to']);
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->withErrors(['test_to' => __('admin.settings.mail_test_failed', ['error' => $e->getMessage()])]);
        }

        return back()->with('success', __('admin.settings.mail_test_sent', ['email' => $data['test_to']]));
    }

    public function editTemplates(MailTemplateService $templates): View
    {
        return view('admin.settings.mail-templates', [
            'templates' => $templates->all(),
            'placeholders' => $templates->placeholderHints(),
        ]);
    }

    public function updateTemplates(Request $request, MailTemplateService $templates): RedirectResponse
    {
        $data = $request->validate([
            'templates' => ['required', 'array'],
            'templates.*.enabled' => ['nullable', 'boolean'],
            'templates.*.subject' => ['nullable', 'string', 'max:255'],
            'templates.*.body' => ['nullable', 'string', 'max:20000'],
        ]);

        $normalized = [];
        foreach ($data['templates'] as $key => $row) {
            $normalized[$key] = [
                'enabled' => ! empty($row['enabled']),
                'subject' => $row['subject'] ?? '',
                'body' => $row['body'] ?? '',
            ];
        }

        $templates->update($normalized);

        return back()->with('success', __('admin.settings.mail_templates_saved'));
    }
}
