<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Wp\V2;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ContentResource;
use App\Models\Comment;
use App\Models\Content;
use App\Models\ContentType;
use App\Models\Media;
use App\Models\Taxonomy;
use App\Models\Term;
use App\Models\User;
use App\Services\Content\ContentService;
use App\Services\Content\RevisionService;
use Illuminate\Http\Request;

/**
 * WordPress-compatible REST surface (read + authenticated write subset).
 */
class WpRestController extends Controller
{
    public function __construct(
        private readonly ContentService $contents,
        private readonly RevisionService $revisions,
    ) {}

    public function types()
    {
        return response()->json(
            ContentType::query()->where('show_in_rest', true)->get()
                ->mapWithKeys(fn ($type) => [
                    $type->slug => [
                        'name' => $type->plural_label,
                        'slug' => $type->slug,
                        'rest_base' => $type->rest_base,
                        'hierarchical' => $type->hierarchical,
                    ],
                ])
        );
    }

    public function statuses()
    {
        return response()->json(collect(ContentStatus::cases())->mapWithKeys(
            fn (ContentStatus $status) => [$status->value => ['name' => $status->value, 'slug' => $status->value]]
        ));
    }

    public function posts(Request $request)
    {
        return $this->collectionByType('post', $request);
    }

    public function pages(Request $request)
    {
        return $this->collectionByType('page', $request);
    }

    public function storePost(Request $request)
    {
        return $this->storeType('post', $request);
    }

    public function updatePost(Request $request, Content $content)
    {
        return $this->updateType('post', $request, $content);
    }

    public function destroyPost(Content $content)
    {
        return $this->destroyType('post', $content);
    }

    public function storePage(Request $request)
    {
        return $this->storeType('page', $request);
    }

    public function updatePage(Request $request, Content $content)
    {
        return $this->updateType('page', $request, $content);
    }

    public function destroyPage(Content $content)
    {
        return $this->destroyType('page', $content);
    }

    public function categories(Request $request)
    {
        return $this->terms('category', $request);
    }

    public function tags(Request $request)
    {
        return $this->terms('post_tag', $request);
    }

    public function taxonomies()
    {
        return response()->json(
            Taxonomy::query()->where('show_in_rest', true)->get()
                ->mapWithKeys(fn ($tax) => [
                    $tax->slug => [
                        'name' => $tax->plural_label,
                        'slug' => $tax->slug,
                        'rest_base' => $tax->rest_base,
                        'hierarchical' => $tax->hierarchical,
                    ],
                ])
        );
    }

    public function media(Request $request)
    {
        $items = Media::query()
            ->latest()
            ->paginate(min(max((int) $request->integer('per_page', 10), 1), 100));

        return response()->json([
            'data' => $items->getCollection()->map(fn (Media $media) => [
                'id' => $media->id,
                'source_url' => $media->url(),
                'mime_type' => $media->mime_type,
                'media_type' => str_starts_with((string) $media->mime_type, 'image/') ? 'image' : 'file',
                'title' => ['rendered' => $media->filename],
                'alt_text' => $media->alt,
                'date' => $media->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function users(Request $request)
    {
        // Public users endpoint: only expose safe profile fields.
        $items = User::query()
            ->orderBy('username')
            ->paginate(min(max((int) $request->integer('per_page', 10), 1), 100));

        return response()->json([
            'data' => $items->getCollection()->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->publicName(),
                'slug' => $user->username,
                'url' => url('/author/'.$user->username),
            ]),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function comments(Request $request)
    {
        $query = Comment::query()->approved()->latest();

        if ($request->filled('post')) {
            $query->where('post_id', (int) $request->integer('post'));
        }

        $items = $query->paginate(min(max((int) $request->integer('per_page', 10), 1), 100));

        return response()->json([
            'data' => $items->getCollection()->map(fn (Comment $comment) => [
                'id' => $comment->id,
                'post' => $comment->post_id,
                'parent' => $comment->parent_id ?: 0,
                'author_name' => $comment->displayName(),
                'date' => $comment->created_at?->toIso8601String(),
                'content' => ['rendered' => $comment->content],
                'status' => $comment->status,
            ]),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function settings()
    {
        return response()->json([
            'title' => \App\Models\CmsSetting::getValue('site_name'),
            'description' => \App\Models\CmsSetting::getValue('site_description'),
            'url' => \App\Models\CmsSetting::getValue('site_url') ?: config('app.url'),
            'email' => \App\Models\CmsSetting::getValue('admin_email'),
            'timezone' => \App\Models\CmsSetting::getValue('timezone') ?: config('app.timezone'),
            'date_format' => \App\Models\CmsSetting::getValue('date_format') ?: 'Y-m-d',
            'language' => \App\Models\CmsSetting::getValue('language') ?: 'en',
        ]);
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->query('search', $request->query('q', '')));
        $results = app(\App\Services\Search\SearchService::class)->search($q, ['content'], 20);

        return response()->json([
            'data' => $results->map(fn (array $row) => [
                'id' => $row['id'],
                'type' => $row['type'],
                'title' => ['rendered' => $row['title']],
                'url' => $row['url'],
                'excerpt' => ['rendered' => $row['excerpt'] ?? ''],
            ])->values(),
        ]);
    }

    private function storeType(string $type, Request $request)
    {
        $this->authorize('create', Content::class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
        ]);

        $content = $this->contents->create($type, [
            'title' => $data['title'],
            'slug' => $data['slug'] ?? null,
            'body' => $data['content'] ?? null,
            'excerpt' => $data['excerpt'] ?? null,
            'status' => $data['status'] ?? 'draft',
        ], $request->user());

        $this->revisions->snapshot($content, $request->user(), 'wp-rest-create');

        return (new ContentResource($content->load(['type', 'author'])))->response()->setStatusCode(201);
    }

    private function updateType(string $type, Request $request, Content $content)
    {
        abort_unless($content->type?->slug === $type, 404);
        $this->authorize('update', $content);

        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
        ]);

        if (array_key_exists('content', $data)) {
            $data['body'] = $data['content'];
            unset($data['content']);
        }

        $content = $this->contents->update($content, $data);

        return new ContentResource($content->load(['type', 'author']));
    }

    private function destroyType(string $type, Content $content)
    {
        abort_unless($content->type?->slug === $type, 404);
        $this->authorize('delete', $content);
        $this->contents->trash($content);

        return response()->json(['deleted' => true, 'id' => $content->id]);
    }

    private function collectionByType(string $type, Request $request)
    {
        $items = Content::query()
            ->ofType($type)
            ->published()
            ->with(['author', 'type', 'terms'])
            ->latest('published_at')
            ->paginate(min(max((int) $request->integer('per_page', 10), 1), 100));

        return ContentResource::collection($items);
    }

    private function terms(string $taxonomySlug, Request $request)
    {
        $taxonomy = Taxonomy::query()->where('slug', $taxonomySlug)->firstOrFail();

        $terms = Term::query()
            ->where('taxonomy_id', $taxonomy->id)
            ->orderBy('name')
            ->paginate(min(max((int) $request->integer('per_page', 10), 1), 100));

        return response()->json([
            'data' => $terms->getCollection()->map(fn (Term $term) => [
                'id' => $term->id,
                'name' => $term->name,
                'slug' => $term->slug,
                'description' => $term->description,
                'parent' => $term->parent_id,
                'count' => $term->count,
            ]),
            'meta' => [
                'current_page' => $terms->currentPage(),
                'last_page' => $terms->lastPage(),
                'per_page' => $terms->perPage(),
                'total' => $terms->total(),
            ],
        ]);
    }
}
