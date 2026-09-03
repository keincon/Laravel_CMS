<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Enums\ContentStatus;
use App\Exceptions\InvalidContentStatusTransition;
use App\Support\Hooks\Hooks;

/**
 * Centralizes valid content status transitions.
 */
final class ContentStatusTransitionService
{
    /**
     * @throws InvalidContentStatusTransition
     */
    public function transition(ContentStatus $from, ContentStatus $to): ContentStatus
    {
        if ($from === $to) {
            return $to;
        }

        if (! $from->canTransitionTo($to)) {
            throw InvalidContentStatusTransition::make($from, $to);
        }

        Hooks::action('content.status.transitioning', $from, $to);

        Hooks::action('content.status.transitioned', $from, $to);

        return $to;
    }

    /**
     * @return list<string>
     */
    public function allowedTargets(ContentStatus $from): array
    {
        return array_map(
            static fn (ContentStatus $status): string => $status->value,
            $from->allowedTransitions(),
        );
    }
}
