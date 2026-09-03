<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Blocks\BlockRegistry;
use App\Support\Blocks\BlockRenderer;
use App\Support\Security\HtmlSanitizer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BlockRendererAndSanitizerTest extends TestCase
{
    #[Test]
    public function it_renders_and_sanitizes_blocks(): void
    {
        $registry = new BlockRegistry;
        $registry->registerDefaults();
        $renderer = new BlockRenderer($registry, new HtmlSanitizer);

        $html = $renderer->render([
            ['type' => 'heading', 'content' => 'Hello', 'attrs' => ['level' => 2]],
            ['type' => 'paragraph', 'content' => 'World'],
            ['type' => 'paragraph', 'content' => '<script>alert(1)</script>Safe'],
            ['type' => 'code', 'content' => 'echo 1;'],
            ['type' => 'embed', 'attrs' => ['src' => 'https://example.com/embed']],
        ]);

        $this->assertStringContainsString('<h2>Hello</h2>', $html);
        $this->assertStringContainsString('<p>World</p>', $html);
        $this->assertStringContainsString('cms-code', $html);
        $this->assertStringContainsString('cms-embed', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    #[Test]
    public function sanitizer_strips_event_handlers_and_javascript_urls(): void
    {
        $sanitizer = new HtmlSanitizer;
        $clean = $sanitizer->sanitize('<a href="javascript:alert(1)" onclick="evil()">x</a><p>ok</p>');

        $this->assertStringContainsString('<p>ok</p>', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
    }
}
