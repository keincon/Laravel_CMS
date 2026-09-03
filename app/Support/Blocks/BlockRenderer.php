<?php

declare(strict_types=1);

namespace App\Support\Blocks;

use App\Support\Hooks\Hooks;
use App\Support\Security\HtmlSanitizer;

/**
 * Renders structured block arrays to sanitized HTML.
 *
 * @phpstan-type Block array{type: string, attrs?: array<string, mixed>, innerBlocks?: list<array<string, mixed>>, content?: string}
 */
final class BlockRenderer
{
    public function __construct(
        private readonly BlockRegistry $registry,
        private readonly HtmlSanitizer $sanitizer,
    ) {}

    /**
     * @param  list<Block>|null  $blocks
     */
    public function render(?array $blocks): string
    {
        if ($blocks === null || $blocks === []) {
            return '';
        }

        $html = '';
        foreach ($blocks as $block) {
            $html .= $this->renderBlock($block);
        }

        return Hooks::filter('content.rendered', $this->sanitizer->sanitize($html));
    }

    /**
     * @param  Block  $block
     */
    public function renderBlock(array $block): string
    {
        $type = (string) ($block['type'] ?? '');
        if ($type === '' || ! $this->registry->has($type)) {
            return '';
        }

        $attrs = is_array($block['attrs'] ?? null) ? $block['attrs'] : [];
        $inner = is_array($block['innerBlocks'] ?? null) ? $block['innerBlocks'] : [];
        $content = (string) ($block['content'] ?? '');

        return match ($type) {
            'paragraph' => '<p>'.$this->escape($content).'</p>',
            'heading' => $this->heading($attrs, $content),
            'image' => $this->image($attrs),
            'gallery' => $this->gallery($attrs),
            'quote' => '<blockquote><p>'.$this->escape($content).'</p></blockquote>',
            'list' => $this->listBlock($attrs, $content),
            'video' => '<video controls src="'.$this->escape((string) ($attrs['src'] ?? '')).'"></video>',
            'audio' => '<audio controls src="'.$this->escape((string) ($attrs['src'] ?? '')).'"></audio>',
            'button' => '<p><a class="cms-button" href="'.$this->escape((string) ($attrs['url'] ?? '#')).'">'.$this->escape((string) ($attrs['label'] ?? ($content ?: 'Button'))).'</a></p>',
            'columns' => '<div class="cms-columns">'.$this->render($inner).'</div>',
            'code' => '<pre class="cms-code"><code>'.$this->escape($content).'</code></pre>',
            'html' => $content, // sanitized by outer HtmlSanitizer pass
            'embed' => $this->embed($attrs),
            'separator' => '<hr class="cms-separator">',
            'spacer' => '<div class="cms-spacer" style="height:'.(int) ($attrs['height'] ?? 24).'px"></div>',
            default => '',
        };
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function embed(array $attrs): string
    {
        $src = $this->escape((string) ($attrs['src'] ?? ''));
        if ($src === '') {
            return '';
        }

        return '<div class="cms-embed"><iframe src="'.$src.'" loading="lazy" title="Embed" allowfullscreen></iframe></div>';
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function heading(array $attrs, string $content): string
    {
        $level = (int) ($attrs['level'] ?? 2);
        $level = max(1, min(6, $level));

        return "<h{$level}>".$this->escape($content)."</h{$level}>";
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function image(array $attrs): string
    {
        $src = $this->escape((string) ($attrs['src'] ?? ''));
        $alt = $this->escape((string) ($attrs['alt'] ?? ''));
        if ($src === '') {
            return '';
        }

        return '<figure><img src="'.$src.'" alt="'.$alt.'" loading="lazy"></figure>';
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function gallery(array $attrs): string
    {
        $images = is_array($attrs['images'] ?? null) ? $attrs['images'] : [];
        $html = '<div class="cms-gallery">';
        foreach ($images as $image) {
            if (! is_array($image)) {
                continue;
            }
            $html .= $this->image($image);
        }

        return $html.'</div>';
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function listBlock(array $attrs, string $content): string
    {
        $ordered = (bool) ($attrs['ordered'] ?? false);
        $items = is_array($attrs['items'] ?? null) ? $attrs['items'] : (preg_split("/\r\n|\n|\r/", $content) ?: []);
        $tag = $ordered ? 'ol' : 'ul';
        $html = "<{$tag}>";
        foreach ($items as $item) {
            $html .= '<li>'.$this->escape((string) $item).'</li>';
        }

        return $html."</{$tag}>";
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
