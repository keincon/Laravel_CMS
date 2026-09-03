<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\ContentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreContentRequest;
use App\Http\Requests\Api\V1\UpdateContentRequest;
use App\Http\Resources\Api\V1\ContentResource;
use App\Models\Content;
use App\Services\Content\ContentService;
use App\Services\Content\RevisionService;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    public function __construct(
        private readonly ContentService $contents,
        private readonly RevisionService $revisions,
    ) {}

    public function index(Request $request)
    {
        $query = Content::query()->with(['type', 'author', 'terms'])->notTrashed();

        if ($request->filled('type')) {
            $query->ofType((string) $request->string('type'));
        }

        if ($request->boolean('public_only', true) && ! $request->user()) {
            $query->published();
        }

        if ($request->filled('status') && $request->user()) {
            $query->where('status', (string) $request->string('status'));
        }

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)->orWhere('body', 'like', $term);
            });
        }

        $paginator = $query->latest('published_at')->latest('id')->paginate(
            min(max((int) $request->integer('per_page', 20), 1), 100)
        );

        return ContentResource::collection($paginator);
    }

    public function show(string $uuidOrSlug)
    {
        $content = Content::query()
            ->with(['type', 'author', 'terms'])
            ->where(function ($q) use ($uuidOrSlug) {
                $q->where('uuid', $uuidOrSlug)->orWhere('slug', $uuidOrSlug);
            })
            ->firstOrFail();

        if (! $content->status instanceof ContentStatus || ! $content->status->isPubliclyVisible()) {
            $this->authorize('view', $content);
        }

        return new ContentResource($content);
    }

    public function store(StoreContentRequest $request)
    {
        $content = $this->contents->create(
            (string) $request->string('type'),
            $request->validated(),
            $request->user(),
        );

        if ($request->filled('term_ids')) {
            $content->terms()->sync($request->input('term_ids', []));
        }

        $this->revisions->snapshot($content, $request->user(), 'created');

        return (new ContentResource($content->load(['type', 'author', 'terms'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateContentRequest $request, Content $content)
    {
        $this->revisions->snapshot($content, $request->user(), 'pre-update');
        $content = $this->contents->update($content, $request->validated());

        if ($request->exists('term_ids')) {
            $content->terms()->sync($request->input('term_ids', []));
        }

        return new ContentResource($content->load(['type', 'author', 'terms']));
    }

    public function destroy(Request $request, Content $content)
    {
        $this->authorize('delete', $content);
        $this->contents->trash($content);

        return response()->json([
            'message' => 'Content moved to trash.',
            'data' => ['id' => $content->id, 'status' => ContentStatus::Trash->value],
        ]);
    }

    public function types()
    {
        return response()->json([
            'data' => \App\Models\ContentType::query()->orderBy('menu_position')->get()->map(fn ($type) => [
                'id' => $type->id,
                'uuid' => $type->uuid,
                'slug' => $type->slug,
                'singular_label' => $type->singular_label,
                'plural_label' => $type->plural_label,
                'hierarchical' => $type->hierarchical,
                'supports' => $type->supports,
                'public' => $type->public,
                'show_in_rest' => $type->show_in_rest,
            ]),
        ]);
    }
}
