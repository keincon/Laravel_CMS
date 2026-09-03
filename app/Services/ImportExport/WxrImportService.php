<?php

declare(strict_types=1);

namespace App\Services\ImportExport;

use App\Models\Media;
use App\Models\Taxonomy;
use App\Models\Term;
use App\Models\User;
use App\Services\Content\ContentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleXMLElement;

/**
 * WordPress WXR (export) importer — posts/pages, authors, terms, attachments.
 */
final class WxrImportService
{
    public function __construct(private readonly ContentService $contents) {}

    /**
     * @return array{imported: int, skipped: int, authors: int, terms: int, media: int, warnings: list<string>}
     */
    public function importFromDisk(string $path, string $disk = 'local', bool $dryRun = false): array
    {
        $xmlString = Storage::disk($disk)->get($path);
        if ($xmlString === null || trim($xmlString) === '') {
            throw new \InvalidArgumentException('WXR file is empty or missing.');
        }

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlString, SimpleXMLElement::class, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            throw new \InvalidArgumentException('Unable to parse WXR XML.');
        }

        $xml->registerXPathNamespace('wp', 'http://wordpress.org/export/1.2/');
        $xml->registerXPathNamespace('content', 'http://purl.org/rss/1.0/modules/content/');
        $xml->registerXPathNamespace('excerpt', 'http://wordpress.org/export/1.2/excerpt/');
        $xml->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');

        $imported = 0;
        $skipped = 0;
        $authors = 0;
        $terms = 0;
        $media = 0;
        $warnings = [];
        /** @var array<string, int> $authorMap login => user id */
        $authorMap = [];
        /** @var array<int, int> $attachmentMap wp attachment id => media id */
        $attachmentMap = [];

        $runner = function () use (
            $xml,
            $dryRun,
            &$imported,
            &$skipped,
            &$authors,
            &$terms,
            &$media,
            &$warnings,
            &$authorMap,
            &$attachmentMap
        ): void {
            $authors += $this->importAuthors($xml, $dryRun, $authorMap, $warnings);
            $terms += $this->importChannelTerms($xml, $dryRun, $warnings);

            $items = $xml->channel->item ?? [];

            // Pass 1: attachments
            foreach ($items as $item) {
                $wp = $item->children('wp', true);
                $postType = (string) ($wp->post_type ?? '');
                if ($postType !== 'attachment') {
                    continue;
                }

                if ($dryRun) {
                    $media++;
                    continue;
                }

                $result = $this->importAttachment($item, $wp, $warnings);
                if ($result !== null) {
                    $attachmentMap[(int) $result['wp_id']] = $result['media_id'];
                    $media++;
                } else {
                    $skipped++;
                }
            }

            // Pass 2: posts/pages
            foreach ($items as $item) {
                $wp = $item->children('wp', true);
                $contentNs = $item->children('content', true);
                $excerptNs = $item->children('excerpt', true);
                $dc = $item->children('dc', true);

                $postType = (string) ($wp->post_type ?? 'post');
                if (! in_array($postType, ['post', 'page'], true)) {
                    if ($postType !== 'attachment') {
                        $skipped++;
                        $warnings[] = "Skipped unsupported post_type [{$postType}]";
                    }
                    continue;
                }

                $status = (string) ($wp->status ?? 'draft');
                $title = (string) ($item->title ?? 'Untitled');
                $slug = (string) ($wp->post_name ?? '');
                $body = (string) ($contentNs->encoded ?? '');
                $excerpt = (string) ($excerptNs->encoded ?? '');
                $login = (string) ($dc->creator ?? '');
                $authorId = $authorMap[$login] ?? null;

                $termIds = $this->collectItemTermIds($item, $dryRun, $warnings);

                $featured = null;
                foreach ($wp->postmeta ?? [] as $meta) {
                    $key = (string) ($meta->meta_key ?? '');
                    $value = (string) ($meta->meta_value ?? '');
                    if ($key === '_thumbnail_id' && isset($attachmentMap[(int) $value])) {
                        $featured = $attachmentMap[(int) $value];
                    }
                }

                if ($dryRun) {
                    $imported++;
                    continue;
                }

                $content = $this->contents->create($postType, [
                    'title' => $title !== '' ? $title : 'Untitled',
                    'slug' => $slug !== '' ? $slug : null,
                    'body' => $body,
                    'excerpt' => $excerpt !== '' ? $excerpt : null,
                    'status' => $status === 'publish' ? 'published' : $status,
                    'published_at' => filled((string) ($wp->post_date_gmt ?? null))
                        ? (string) $wp->post_date_gmt
                        : null,
                    'author_id' => $authorId,
                    'featured_media_id' => $featured,
                ]);

                if ($termIds !== []) {
                    $content->terms()->sync(array_values(array_unique($termIds)));
                }

                $imported++;
            }
        };

        if ($dryRun) {
            $runner();
        } else {
            DB::transaction($runner);
        }

        return compact('imported', 'skipped', 'authors', 'terms', 'media', 'warnings');
    }

    /**
     * @param  array<string, int>  $authorMap
     * @param  list<string>  $warnings
     */
    private function importAuthors(SimpleXMLElement $xml, bool $dryRun, array &$authorMap, array &$warnings): int
    {
        $count = 0;
        $wpAuthors = $xml->channel->children('wp', true)->author ?? [];

        foreach ($wpAuthors as $author) {
            $login = (string) ($author->author_login ?? '');
            $email = (string) ($author->author_email ?? '');
            $display = (string) ($author->author_display_name ?? $login);
            if ($login === '') {
                $warnings[] = 'Skipped author with empty login.';
                continue;
            }

            $count++;
            if ($dryRun) {
                continue;
            }

            $user = User::query()->where('username', $login)->first()
                ?? (filled($email) ? User::query()->where('email', $email)->first() : null);

            if (! $user) {
                $user = User::query()->create([
                    'name' => $display !== '' ? $display : $login,
                    'username' => $login,
                    'display_name' => $display !== '' ? $display : $login,
                    'email' => filled($email) ? $email : $login.'@wxr.import.local',
                    'password' => Hash::make(Str::random(32)),
                ]);
            }

            $authorMap[$login] = $user->id;
        }

        return $count;
    }

    /**
     * @param  list<string>  $warnings
     */
    private function importChannelTerms(SimpleXMLElement $xml, bool $dryRun, array &$warnings): int
    {
        $count = 0;
        $wp = $xml->channel->children('wp', true);

        foreach ($wp->category ?? [] as $category) {
            $slug = (string) ($category->category_nicename ?? '');
            $name = (string) ($category->cat_name ?? $slug);
            if ($slug === '') {
                continue;
            }
            $count++;
            if (! $dryRun) {
                $this->upsertTerm('category', $name, $slug, (string) ($category->category_description ?? ''));
            }
        }

        foreach ($wp->tag ?? [] as $tag) {
            $slug = (string) ($tag->tag_slug ?? '');
            $name = (string) ($tag->tag_name ?? $slug);
            if ($slug === '') {
                continue;
            }
            $count++;
            if (! $dryRun) {
                $this->upsertTerm('post_tag', $name, $slug, (string) ($tag->tag_description ?? ''));
            }
        }

        return $count;
    }

    /**
     * @param  list<string>  $warnings
     * @return list<int>
     */
    private function collectItemTermIds(SimpleXMLElement $item, bool $dryRun, array &$warnings): array
    {
        $ids = [];
        foreach ($item->category ?? [] as $cat) {
            $domain = (string) ($cat['domain'] ?? 'category');
            $slug = (string) ($cat['nicename'] ?? Str::slug((string) $cat));
            $name = (string) $cat;
            if ($slug === '') {
                continue;
            }

            $taxonomySlug = match ($domain) {
                'post_tag', 'tag' => 'post_tag',
                default => 'category',
            };

            if ($dryRun) {
                continue;
            }

            $term = $this->upsertTerm($taxonomySlug, $name !== '' ? $name : $slug, $slug);
            $ids[] = $term->id;
        }

        return $ids;
    }

    private function upsertTerm(string $taxonomySlug, string $name, string $slug, string $description = ''): Term
    {
        $taxonomy = Taxonomy::query()->where('slug', $taxonomySlug)->firstOrFail();

        return Term::query()->updateOrCreate(
            [
                'taxonomy_id' => $taxonomy->id,
                'slug' => $slug,
            ],
            [
                'name' => $name,
                'description' => $description !== '' ? $description : null,
            ]
        );
    }

    /**
     * @param  list<string>  $warnings
     * @return array{wp_id: int, media_id: int}|null
     */
    private function importAttachment(SimpleXMLElement $item, SimpleXMLElement $wp, array &$warnings): ?array
    {
        $wpId = (int) ($wp->post_id ?? 0);
        $url = (string) ($wp->attachment_url ?? ($item->guid ?? ''));
        $title = (string) ($item->title ?? 'attachment');

        if ($url === '') {
            $warnings[] = "Attachment #{$wpId} missing URL.";

            return null;
        }

        $filename = basename(parse_url($url, PHP_URL_PATH) ?: $title) ?: 'attachment.bin';
        $filename = Str::slug(pathinfo($filename, PATHINFO_FILENAME)).'.'.(pathinfo($filename, PATHINFO_EXTENSION) ?: 'bin');
        $path = 'imports/wxr/'.now()->format('Y/m').'/'.$filename;

        try {
            $response = Http::timeout(20)->withOptions(['allow_redirects' => true])->get($url);
            if (! $response->successful()) {
                $warnings[] = "Failed to download attachment [{$url}] HTTP ".$response->status();
                // Store remote reference stub so featured-image mapping still works.
                $media = Media::query()->create([
                    'disk' => 'public',
                    'path' => $path.'.remote.txt',
                    'filename' => $filename,
                    'mime_type' => 'text/plain',
                    'size' => 0,
                    'alt' => $title,
                ]);
                Storage::disk('public')->put($path.'.remote.txt', $url);

                return ['wp_id' => $wpId, 'media_id' => $media->id];
            }

            $bytes = $response->body();
            Storage::disk('public')->put($path, $bytes);
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($bytes) ?: 'application/octet-stream';

            $media = Media::query()->create([
                'disk' => 'public',
                'path' => $path,
                'filename' => $filename,
                'mime_type' => $mime,
                'size' => strlen($bytes),
                'alt' => $title,
            ]);

            return ['wp_id' => $wpId, 'media_id' => $media->id];
        } catch (\Throwable $e) {
            $warnings[] = "Attachment #{$wpId}: ".$e->getMessage();

            return null;
        }
    }
}
