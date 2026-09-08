<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\ContentType;
use App\Models\Taxonomy;
use App\Services\Content\ContentService;
use App\Services\Content\RevisionService;
use App\Support\Blocks\BlockRenderer;
use Illuminate\Http\Request;

class ContentAdminController extends Controller
{
    public function __construct(
        private readonly ContentService $contents,
        private readonly RevisionService $revisions,
        private readonly BlockRenderer $blocks,
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Content::class);

        $typeSlug = (string) $request->string('type', 'post');
        $status = (string) $request->string('status', 'all');
        $q = (string) $request->string('q');

        $type = ContentType::query()->where('slug', $typeSlug)->firstOrFail();

        $query = Content::query()->with(['author', 'type'])->where('content_type_id', $type->id);

        if ($status === 'trash') {
            $query->where('status', ContentStatus::Trash);
        } elseif ($status !== 'all') {
            $query->where('status', $status)->where('status', '!=', ContentStatus::Trash);
        } else {
            $query->where('status', '!=', ContentStatus::Trash);
        }

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->where('title', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%");
            });
        }

        $counts = [
            'all' => Content::query()->where('content_type_id', $type->id)->where('status', '!=', ContentStatus::Trash)->count(),
            'published' => Content::query()->where('content_type_id', $type->id)->where('status', ContentStatus::Published)->count(),
            'draft' => Content::query()->where('content_type_id', $type->id)->where('status', ContentStatus::Draft)->count(),
            'pending' => Content::query()->where('content_type_id', $type->id)->where('status', ContentStatus::Pending)->count(),
            'scheduled' => Content::query()->where('content_type_id', $type->id)->where('status', ContentStatus::Scheduled)->count(),
            'trash' => Content::query()->where('content_type_id', $type->id)->where('status', ContentStatus::Trash)->count(),
        ];

        return view('admin.contents.index', [
            'contents' => $query->latest('updated_at')->paginate(20)->withQueryString(),
            'type' => $type,
            'types' => ContentType::query()->orderBy('menu_position')->get(),
            'status' => $status,
            'q' => $q,
            'counts' => $counts,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Content::class);
        $typeSlug = (string) $request->string('type', 'post');
        $type = ContentType::query()->where('slug', $typeSlug)->firstOrFail();

        return view('admin.contents.edit', [
            'content' => new Content(['status' => ContentStatus::Draft, 'content_type_id' => $type->id]),
            'type' => $type,
            'types' => ContentType::query()->orderBy('menu_position')->get(),
            'parents' => $type->hierarchical
                ? Content::query()->where('content_type_id', $type->id)->orderBy('title')->get()
                : collect(),
            'taxonomies' => $this->taxonomiesForType($type),
            'selectedTermIds' => old('term_ids', []),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Content::class);

        $data = $request->validate([
            'type' => ['required', 'string', 'exists:content_types,slug'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'blocks_json' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
            'status' => ['required', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:contents,id'],
            'scheduled_at' => ['nullable', 'date'],
            'comment_status' => ['nullable', 'in:open,closed'],
            'template' => ['nullable', 'string', 'max:100'],
            'featured_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'term_ids' => ['nullable', 'array'],
            'term_ids.*' => ['integer', 'exists:terms,id'],
        ]);

        $data = $this->applyBlocks($data);

        if (($request->input('action') === 'publish')) {
            $data['status'] = ContentStatus::Published->value;
        } elseif ($request->input('action') === 'draft') {
            $data['status'] = ContentStatus::Draft->value;
        }

        $content = $this->contents->create($data['type'], $data, $request->user());
        $this->revisions->snapshot($content, $request->user(), 'created');

        return redirect()
            ->route('admin.contents.edit', $content)
            ->with('status', 'Content created.');
    }

    public function edit(Content $content)
    {
        $this->authorize('update', $content);
        $content->load(['type', 'author', 'terms']);

        return view('admin.contents.edit', [
            'content' => $content,
            'type' => $content->type,
            'types' => ContentType::query()->orderBy('menu_position')->get(),
            'parents' => $content->type?->hierarchical
                ? Content::query()
                    ->where('content_type_id', $content->content_type_id)
                    ->where('id', '!=', $content->id)
                    ->orderBy('title')
                    ->get()
                : collect(),
            'revisions' => $content->revisions()->with('user')->limit(20)->get(),
            'taxonomies' => $this->taxonomiesForType($content->type),
            'selectedTermIds' => old('term_ids', $content->terms->pluck('id')->all()),
        ]);
    }

    public function update(Request $request, Content $content)
    {
        $this->authorize('update', $content);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'blocks_json' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
            'status' => ['required', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:contents,id'],
            'scheduled_at' => ['nullable', 'date'],
            'comment_status' => ['nullable', 'in:open,closed'],
            'template' => ['nullable', 'string', 'max:100'],
            'featured_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'term_ids' => ['nullable', 'array'],
            'term_ids.*' => ['integer', 'exists:terms,id'],
            'action' => ['nullable', 'string'],
        ]);

        $action = $data['action'] ?? null;
        unset($data['action']);

        if ($action === 'publish') {
            $data['status'] = ContentStatus::Published->value;
        } elseif ($action === 'draft') {
            $data['status'] = ContentStatus::Draft->value;
        } elseif ($action === 'trash') {
            $data['status'] = ContentStatus::Trash->value;
        }

        $data['term_ids'] = $data['term_ids'] ?? [];
        $data = $this->applyBlocks($data);
        $this->revisions->snapshot($content, $request->user(), 'pre-update');
        $content = $this->contents->update($content, $data);

        return redirect()
            ->route('admin.contents.edit', $content)
            ->with('status', 'Content updated.');
    }

    public function destroy(Content $content)
    {
        $this->authorize('delete', $content);
        $this->contents->trash($content);

        return redirect()
            ->route('admin.contents.index', ['type' => $content->type?->slug ?? 'post'])
            ->with('status', 'Content moved to trash.');
    }

    public function autosave(Request $request, Content $content)
    {
        $this->authorize('update', $content);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'blocks_json' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
        ]);

        $data = $this->applyBlocks($data);
        if (! empty($data['title'])) {
            $content = $this->contents->update($content, array_filter([
                'title' => $data['title'] ?? null,
                'body' => $data['body'] ?? null,
                'blocks' => $data['blocks'] ?? null,
                'excerpt' => $data['excerpt'] ?? null,
            ], fn ($v) => $v !== null));
        }

        $revision = $this->revisions->snapshot($content, $request->user(), 'autosave');

        return response()->json([
            'ok' => true,
            'message' => 'Autosaved.',
            'revision' => $revision->revision_number,
            'saved_at' => now()->toIso8601String(),
        ]);
    }

    public function restoreRevision(Content $content, \App\Models\ContentRevision $revision)
    {
        $this->authorize('update', $content);
        $this->revisions->restore($content, $revision);
        app(\App\Services\Content\DualWriteContentSync::class)->syncFromContent($content->fresh(['type', 'terms.taxonomy']));

        return redirect()
            ->route('admin.contents.edit', $content)
            ->with('status', 'Revision restored.');
    }

    public function compareRevisions(Request $request, Content $content)
    {
        $this->authorize('update', $content);

        $leftId = (int) $request->integer('left');
        $rightId = (int) $request->integer('right');

        $left = $content->revisions()->whereKey($leftId)->firstOrFail();
        $right = $content->revisions()->whereKey($rightId)->firstOrFail();

        return view('admin.contents.compare', [
            'content' => $content,
            'left' => $left,
            'right' => $right,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Taxonomy>
     */
    private function taxonomiesForType(?ContentType $type)
    {
        if (! $type) {
            return collect();
        }

        return Taxonomy::query()
            ->with(['terms' => fn ($q) => $q->orderBy('name')])
            ->whereJsonContains('content_types', $type->slug)
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyBlocks(array $data): array
    {
        unset($data['blocks_json']);
        $raw = request()->input('blocks_json');
        if (! is_string($raw) || trim($raw) === '') {
            return $data;
        }

        try {
            $blocks = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $data;
        }

        if (! is_array($blocks)) {
            return $data;
        }

        $data['blocks'] = $blocks;

        // Empty canvas must not wipe legacy HTML stored in the body textarea.
        if ($blocks === []) {
            if (! filled($data['body'] ?? null)) {
                $data['body'] = $this->blocks->render($blocks);
            }

            return $data;
        }

        $data['body'] = $this->blocks->render($blocks);

        return $data;
    }
}
