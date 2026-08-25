<?php

namespace App\Services;

use App\Models\CmsSetting;
use Illuminate\Support\Facades\Session;

class UIFrameworkService
{
    public const TAILWIND = 'tailwind';

    public const BOOTSTRAP = 'bootstrap';

    public function current(): string
    {
        $fromSession = Session::get('setup.appearance.ui_framework');
        if (is_string($fromSession) && $this->isValid($fromSession)) {
            return $fromSession;
        }

        if (app(InstallationService::class)->isInstalled()) {
            $stored = CmsSetting::getValue('ui_framework');
            if (is_string($stored) && $this->isValid($stored)) {
                return $stored;
            }
        }

        return config('cms.default_ui_framework', self::TAILWIND);
    }

    public function isValid(string $framework): bool
    {
        return array_key_exists($framework, config('cms.ui_frameworks', []));
    }

    public function set(string $framework): void
    {
        if (! $this->isValid($framework)) {
            $framework = config('cms.default_ui_framework', self::TAILWIND);
        }

        CmsSetting::setValue('ui_framework', $framework);
    }

    /**
     * @return array<string, array{label: string, description: string}>
     */
    public function available(): array
    {
        return config('cms.ui_frameworks', []);
    }

    /**
     * CDN stylesheet URLs for the active framework (no NPM required).
     *
     * @return list<string>
     */
    public function stylesheetUrls(?string $framework = null): array
    {
        $framework ??= $this->current();

        return match ($framework) {
            self::BOOTSTRAP => [
                'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
            ],
            default => [
                'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css',
            ],
        };
    }

    /**
     * CDN script URLs for the active framework.
     *
     * @return list<string>
     */
    public function scriptUrls(?string $framework = null): array
    {
        $framework ??= $this->current();

        return match ($framework) {
            self::BOOTSTRAP => [
                'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
                'https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js',
            ],
            default => [
                'https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js',
            ],
        };
    }

    /**
     * Resolve a semantic class map for Blade UI components.
     *
     * @return array<string, string>
     */
    public function classes(?string $framework = null): array
    {
        $framework ??= $this->current();

        if ($framework === self::BOOTSTRAP) {
            return [
                'btn_primary' => 'btn btn-primary',
                'btn_secondary' => 'btn btn-outline-secondary',
                'btn_success' => 'btn btn-success',
                'btn_block' => 'w-100',
                'input' => 'form-control',
                'label' => 'form-label',
                'select' => 'form-select',
                'card' => 'card shadow-sm border-0',
                'card_body' => 'card-body p-4 p-md-5',
                'alert_success' => 'alert alert-success',
                'alert_danger' => 'alert alert-danger',
                'alert_info' => 'alert alert-info',
                'text_muted' => 'text-muted',
                'heading' => 'h2 mb-2',
                'subheading' => 'text-secondary mb-4',
                'step_active' => 'badge text-bg-primary',
                'step_done' => 'badge text-bg-success',
                'step_pending' => 'badge text-bg-light text-dark border',
                'check_ok' => 'text-success',
                'check_fail' => 'text-danger',
                'framework_card' => 'card h-100 border',
                'framework_card_selected' => 'card h-100 border border-primary border-2',
            ];
        }

        return [
            'btn_primary' => 'inline-flex items-center justify-center px-5 py-2.5 rounded-lg bg-sky-600 text-white font-medium hover:bg-sky-700 transition focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2',
            'btn_secondary' => 'inline-flex items-center justify-center px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 font-medium hover:bg-slate-50 transition focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2',
            'btn_success' => 'inline-flex items-center justify-center px-5 py-2.5 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2',
            'btn_block' => 'w-full',
            'input' => 'block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-900 shadow-sm focus:border-sky-500 focus:ring-sky-500',
            'label' => 'block text-sm font-medium text-slate-700 mb-1.5',
            'select' => 'block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-slate-900 shadow-sm focus:border-sky-500 focus:ring-sky-500',
            'card' => 'bg-white rounded-2xl shadow-lg border border-slate-100',
            'card_body' => 'p-6 sm:p-10',
            'alert_success' => 'rounded-lg bg-emerald-50 text-emerald-800 px-4 py-3 border border-emerald-200',
            'alert_danger' => 'rounded-lg bg-red-50 text-red-800 px-4 py-3 border border-red-200',
            'alert_info' => 'rounded-lg bg-sky-50 text-sky-800 px-4 py-3 border border-sky-200',
            'text_muted' => 'text-slate-500',
            'heading' => 'text-2xl sm:text-3xl font-semibold text-slate-900 tracking-tight',
            'subheading' => 'text-slate-500 mt-2 mb-6',
            'step_active' => 'inline-flex items-center justify-center w-7 h-7 rounded-full bg-sky-600 text-white text-xs font-semibold',
            'step_done' => 'inline-flex items-center justify-center w-7 h-7 rounded-full bg-emerald-500 text-white text-xs font-semibold',
            'step_pending' => 'inline-flex items-center justify-center w-7 h-7 rounded-full bg-slate-200 text-slate-600 text-xs font-semibold',
            'check_ok' => 'text-emerald-600',
            'check_fail' => 'text-red-600',
            'framework_card' => 'rounded-xl border-2 border-slate-200 p-6 text-center hover:border-sky-300 transition cursor-pointer h-full',
            'framework_card_selected' => 'rounded-xl border-2 border-sky-600 bg-sky-50 p-6 text-center h-full',
        ];
    }

    public function class(string $key, ?string $framework = null): string
    {
        return $this->classes($framework)[$key] ?? '';
    }
}
