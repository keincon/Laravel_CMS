<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\ContentStatus;
use App\Exceptions\InvalidContentStatusTransition;
use App\Services\Content\ContentStatusTransitionService;
use App\Support\Hooks\HookRegistry;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContentStatusAndHooksTest extends TestCase
{
    #[Test]
    public function it_allows_valid_status_transitions(): void
    {
        $service = new ContentStatusTransitionService;

        $this->assertSame(
            ContentStatus::Published,
            $service->transition(ContentStatus::Draft, ContentStatus::Published),
        );

        $this->assertSame(
            ContentStatus::Draft,
            $service->transition(ContentStatus::Trash, ContentStatus::Draft),
        );
    }

    #[Test]
    public function it_rejects_invalid_status_transitions(): void
    {
        $this->expectException(InvalidContentStatusTransition::class);

        (new ContentStatusTransitionService)->transition(
            ContentStatus::Trash,
            ContentStatus::Published,
        );
    }

    #[Test]
    public function hooks_filter_and_action_work_by_priority(): void
    {
        $hooks = new HookRegistry;
        $log = [];

        $hooks->addAction('demo', function () use (&$log) {
            $log[] = 'b';
        }, 20);
        $hooks->addAction('demo', function () use (&$log) {
            $log[] = 'a';
        }, 5);

        $hooks->doAction('demo');
        $this->assertSame(['a', 'b'], $log);

        $hooks->addFilter('title', fn (string $v) => $v.'!');
        $hooks->addFilter('title', fn (string $v) => strtoupper($v), 5);

        $this->assertSame('HELLO!', $hooks->applyFilters('title', 'hello'));
    }

    #[Test]
    public function legacy_status_mapping_works(): void
    {
        $this->assertSame(ContentStatus::Published, ContentStatus::fromLegacy('publish'));
        $this->assertSame('publish', ContentStatus::Published->toLegacy());
    }
}
