<?php

namespace App\Services;

use App\Models\ContentRevision;
use App\Models\Footer;
use App\Models\Header;
use App\Models\LayoutSetting;
use App\Models\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class LayoutBootstrapService
{
    public function __construct(
        protected LayoutResolverService $layouts,
    ) {}

    public function ensureDefaults(?Page $home = null, ?Page $blog = null): void
    {
        $header = Header::query()->firstOrCreate(
            ['slug' => 'main-header'],
            [
                'name' => 'Main Header',
                'content' => Header::defaultStructure(),
                'draft_content' => Header::defaultStructure(),
                'settings' => [],
                'status' => 'published',
                'is_default' => true,
            ]
        );

        $footer = Footer::query()->firstOrCreate(
            ['slug' => 'main-footer'],
            [
                'name' => 'Main Footer',
                'content' => Footer::defaultStructure(),
                'draft_content' => Footer::defaultStructure(),
                'settings' => [],
                'status' => 'published',
                'is_default' => true,
            ]
        );

        $existing = LayoutSetting::query()->first();
        if (! $existing) {
            LayoutSetting::query()->create([
                'default_header_id' => $header->id,
                'default_footer_id' => $footer->id,
                'container_width' => 1200,
                'content_width' => 900,
                'sidebar_width' => 300,
                'page_layout' => 'standard',
                'post_layout' => 'standard',
                'sidebar_position' => 'right',
                'homepage_type' => 'static',
                'homepage_page_id' => $home?->id,
                'posts_page_id' => $blog?->id,
            ]);
        } else {
            $existing->fill(array_filter([
                'default_header_id' => $existing->default_header_id ?: $header->id,
                'default_footer_id' => $existing->default_footer_id ?: $footer->id,
                'homepage_page_id' => $existing->homepage_page_id ?: $home?->id,
                'posts_page_id' => $existing->posts_page_id ?: $blog?->id,
            ]))->save();
        }

        $this->layouts->clearCaches();
        app(DynamicPageService::class)->ensureDefaults();
    }

    public function saveHeaderDraft(Header $header, array $structure, ?string $note = null): Header
    {
        $header->draft_content = $structure;
        $header->status = 'draft';
        $header->save();

        $this->revision($header, ['draft_content' => $structure], $note ?? 'Header draft saved');
        Header::forgetCache();

        return $header;
    }

    public function publishHeader(Header $header): Header
    {
        $structure = $header->editableStructure();
        $header->content = $structure;
        $header->draft_content = $structure;
        $header->status = 'published';
        $header->save();

        $this->revision($header, ['content' => $structure], 'Header published');
        $this->layouts->clearCaches();

        return $header;
    }

    public function saveFooterDraft(Footer $footer, array $structure, ?string $note = null): Footer
    {
        $footer->draft_content = $structure;
        $footer->status = 'draft';
        $footer->save();

        $this->revision($footer, ['draft_content' => $structure], $note ?? 'Footer draft saved');
        Footer::forgetCache();

        return $footer;
    }

    public function publishFooter(Footer $footer): Footer
    {
        $structure = $footer->editableStructure();
        $footer->content = $structure;
        $footer->draft_content = $structure;
        $footer->status = 'published';
        $footer->save();

        $this->revision($footer, ['content' => $structure], 'Footer published');
        $this->layouts->clearCaches();

        return $footer;
    }

    protected function revision(Model $model, array $payload, string $note): void
    {
        ContentRevision::query()->create([
            'revisable_type' => $model::class,
            'revisable_id' => $model->getKey(),
            'user_id' => Auth::id(),
            'payload' => $payload,
            'note' => $note,
        ]);
    }
}
