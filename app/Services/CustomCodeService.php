<?php

namespace App\Services;

use App\Models\CmsSetting;
use App\Models\Content;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Database\Eloquent\Model;

class CustomCodeService
{
    public function additionalCss(): string
    {
        return (string) CmsSetting::getValue('additional_css', '');
    }

    public function headerScripts(): string
    {
        return (string) CmsSetting::getValue('header_scripts', '');
    }

    public function footerScripts(): string
    {
        return (string) CmsSetting::getValue('footer_scripts', '');
    }

    public function customHtmlHead(): string
    {
        return (string) CmsSetting::getValue('custom_html_head', '');
    }

    public function customHtmlBodyOpen(): string
    {
        return (string) CmsSetting::getValue('custom_html_body_open', '');
    }

    public function customHtmlBodyClose(): string
    {
        return (string) CmsSetting::getValue('custom_html_body_close', '');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data): void
    {
        foreach ([
            'additional_css',
            'header_scripts',
            'footer_scripts',
            'custom_html_head',
            'custom_html_body_open',
            'custom_html_body_close',
        ] as $key) {
            if (array_key_exists($key, $data)) {
                CmsSetting::setValue($key, (string) ($data[$key] ?? ''), 'string');
            }
        }
    }

    public function entryCss(Page|Post|Content|null $entry): string
    {
        return $this->entryField($entry, 'custom_css');
    }

    public function entryJs(Page|Post|Content|null $entry): string
    {
        return $this->entryField($entry, 'custom_js');
    }

    public function entryHtml(Page|Post|Content|null $entry): string
    {
        return $this->entryField($entry, 'custom_html');
    }

    private function entryField(?Model $entry, string $attribute): string
    {
        if (! $entry) {
            return '';
        }

        return (string) ($entry->{$attribute} ?? '');
    }
}
