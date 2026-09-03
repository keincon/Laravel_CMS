<?php

declare(strict_types=1);

namespace App\Services\ImportExport;

use App\Enums\ContentStatus;
use App\Models\Content;
use App\Models\ContentType;
use App\Services\Content\ContentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * JSON content pack import/export (Phase 14 foundation).
 */
final class ContentPackService
{
    public function __construct(private readonly ContentService $contents) {}

    /**
     * @return array{path: string, count: int}
     */
    public function exportToDisk(string $disk = 'local', string $filename = 'exports/content-pack.json'): array
    {
        $payload = [
            'format' => 'laravelpress.content_pack.v1',
            'exported_at' => now()->toIso8601String(),
            'contents' => Content::query()
                ->with(['type', 'terms.taxonomy'])
                ->where('status', '!=', ContentStatus::Trash)
                ->get()
                ->map(fn (Content $content) => [
                    'type' => $content->type?->slug,
                    'title' => $content->title,
                    'slug' => $content->slug,
                    'body' => $content->body,
                    'excerpt' => $content->excerpt,
                    'blocks' => $content->blocks,
                    'status' => $content->status instanceof ContentStatus
                        ? $content->status->value
                        : $content->status,
                    'published_at' => $content->published_at?->toIso8601String(),
                    'terms' => $content->terms->map(fn ($term) => [
                        'taxonomy' => $term->taxonomy?->slug,
                        'slug' => $term->slug,
                        'name' => $term->name,
                    ])->all(),
                ])->all(),
        ];

        Storage::disk($disk)->put($filename, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return [
            'path' => $filename,
            'count' => count($payload['contents']),
        ];
    }

    /**
     * @return array{imported: int, skipped: int}
     */
    public function importFromDisk(string $path, string $disk = 'local', bool $dryRun = false): array
    {
        $raw = Storage::disk($disk)->get($path);
        $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        if (($payload['format'] ?? null) !== 'laravelpress.content_pack.v1') {
            throw new \InvalidArgumentException('Unsupported content pack format.');
        }

        $imported = 0;
        $skipped = 0;

        $runner = function () use ($payload, $dryRun, &$imported, &$skipped): void {
            foreach ($payload['contents'] ?? [] as $row) {
                $typeSlug = (string) ($row['type'] ?? '');
                if ($typeSlug === '' || ! ContentType::query()->where('slug', $typeSlug)->exists()) {
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $imported++;
                    continue;
                }

                $this->contents->create($typeSlug, [
                    'title' => $row['title'] ?? 'Untitled',
                    'slug' => $row['slug'] ?? null,
                    'body' => $row['body'] ?? null,
                    'excerpt' => $row['excerpt'] ?? null,
                    'blocks' => $row['blocks'] ?? null,
                    'status' => $row['status'] ?? 'draft',
                    'published_at' => $row['published_at'] ?? null,
                ]);
                $imported++;
            }
        };

        if ($dryRun) {
            $runner();
        } else {
            DB::transaction($runner);
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }
}
