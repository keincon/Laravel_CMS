<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Content;
use App\Services\AuditLogService;
use App\Support\Hooks\Hooks;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

final class RecordAuditLog
{
    public function __construct(
        private readonly AuditLogService $audit,
    ) {}

    public function subscribe(): void
    {
        Hooks::addAction('content.created', function (Content $content): void {
            $this->audit->record('content.created', $content, null, $this->contentSnapshot($content));
        });

        Hooks::addAction('content.updated', function (Content $content): void {
            $this->audit->record('content.updated', $content, null, $this->contentSnapshot($content));
        });

        Hooks::addAction('content.published', function (Content $content): void {
            $this->audit->record('content.published', $content, null, $this->contentSnapshot($content));
        });

        Hooks::addAction('settings.updated', function (string $key, mixed $value): void {
            $this->audit->record('settings.updated', null, null, [
                'key' => $key,
                'value' => is_scalar($value) || $value === null ? $value : json_encode($value),
            ]);
        });
    }

    public function handleLogin(Login $event): void
    {
        $this->audit->record('auth.login', $event->user instanceof \Illuminate\Database\Eloquent\Model ? $event->user : null, null, [
            'guard' => $event->guard,
        ], $event->user instanceof \App\Models\User ? $event->user : null);
    }

    public function handleLogout(Logout $event): void
    {
        $user = $event->user instanceof \App\Models\User ? $event->user : null;
        $this->audit->record(
            'auth.logout',
            $user,
            null,
            ['guard' => $event->guard],
            $user,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function contentSnapshot(Content $content): array
    {
        $content->loadMissing('type');

        return [
            'id' => $content->id,
            'type' => $content->type?->slug,
            'title' => $content->title,
            'slug' => $content->slug,
            'status' => $content->status instanceof \BackedEnum
                ? $content->status->value
                : (string) $content->status,
        ];
    }
}
