<?php

namespace App\Services;

use App\Models\ThemeSetting;
use Illuminate\Support\Facades\Cache;

class ThemeService
{
    public function settings(): ThemeSetting
    {
        return ThemeSetting::current();
    }

    public function primary(): string
    {
        return $this->settings()->primary_color;
    }

    public function secondary(): string
    {
        return $this->settings()->secondary_color;
    }

    public function accent(): string
    {
        return $this->settings()->accent_color;
    }

    public function background(): string
    {
        return $this->settings()->background_color;
    }

    public function text(): string
    {
        return $this->settings()->text_color;
    }

    public function mode(): string
    {
        return $this->settings()->color_mode ?: 'system';
    }

    /**
     * @return array<string, string>
     */
    public function colors(): array
    {
        $s = $this->settings();

        return [
            'primary' => $s->primary_color,
            'secondary' => $s->secondary_color,
            'accent' => $s->accent_color,
            'success' => $s->success_color,
            'warning' => $s->warning_color,
            'danger' => $s->danger_color,
            'info' => $s->info_color,
            'background' => $s->background_color,
            'surface' => $s->surface_color,
            'text' => $s->text_color,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function cssVariables(): array
    {
        return Cache::remember('theme.css_variables', 3600, function () {
            $colors = $this->colors();
            $vars = [];

            foreach ($colors as $key => $value) {
                $vars['--color-'.$key] = $value;
                $vars['--bs-'.$key] = $value;
            }

            // Bootstrap semantic aliases
            $vars['--bs-body-bg'] = $colors['background'];
            $vars['--bs-body-color'] = $colors['text'];
            $vars['--bs-primary'] = $colors['primary'];
            $vars['--bs-secondary'] = $colors['secondary'];
            $vars['--bs-success'] = $colors['success'];
            $vars['--bs-warning'] = $colors['warning'];
            $vars['--bs-danger'] = $colors['danger'];
            $vars['--bs-info'] = $colors['info'];

            return $vars;
        });
    }

    public function cssBlock(): string
    {
        $lines = [':root {'];
        foreach ($this->cssVariables() as $name => $value) {
            $lines[] = '    '.$name.': '.$value.';';
        }
        $lines[] = '}';
        $lines[] = '';
        $lines[] = '[data-theme="dark"] {';
        $lines[] = '    --color-background: #0f172a;';
        $lines[] = '    --color-surface: #1e293b;';
        $lines[] = '    --color-text: #e2e8f0;';
        $lines[] = '    --bs-body-bg: #0f172a;';
        $lines[] = '    --bs-body-color: #e2e8f0;';
        $lines[] = '}';

        return implode("\n", $lines);
    }

    /**
     * Public-safe theme payload for API.
     *
     * @return array{name: string, colors: array<string, string>, mode: string, ui_framework: string}
     */
    public function publicConfig(): array
    {
        return [
            'name' => $this->settings()->theme ?: 'Default',
            'colors' => $this->colors(),
            'mode' => $this->mode(),
            'ui_framework' => app(UIFrameworkService::class)->current(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data): ThemeSetting
    {
        $settings = $this->settings();
        $settings->fill($data);
        $settings->save();
        ThemeSetting::forgetCache();

        return $settings->fresh();
    }

    public function reset(): ThemeSetting
    {
        return $this->update(ThemeSetting::defaults());
    }
}
