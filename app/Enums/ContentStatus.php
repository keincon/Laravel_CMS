<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Canonical content lifecycle statuses for LaravelPress.
 */
enum ContentStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Private = 'private';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Trash = 'trash';

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Pending, self::Private, self::Scheduled, self::Published, self::Trash],
            self::Pending => [self::Draft, self::Published, self::Private, self::Trash],
            self::Private => [self::Draft, self::Published, self::Trash],
            self::Scheduled => [self::Draft, self::Published, self::Trash],
            self::Published => [self::Draft, self::Private, self::Trash],
            self::Trash => [self::Draft],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function isPubliclyVisible(): bool
    {
        return $this === self::Published;
    }

    /**
     * Map legacy CMS status strings onto the canonical enum.
     */
    public static function fromLegacy(string $status): self
    {
        return match (strtolower($status)) {
            'publish', 'published' => self::Published,
            'future', 'scheduled' => self::Scheduled,
            'pending' => self::Pending,
            'private' => self::Private,
            'trash' => self::Trash,
            default => self::Draft,
        };
    }

    public function toLegacy(): string
    {
        return match ($this) {
            self::Published => 'publish',
            self::Scheduled => 'future',
            default => $this->value,
        };
    }
}
