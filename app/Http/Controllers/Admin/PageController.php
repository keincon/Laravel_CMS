<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentRevision;
use App\Models\Footer;
use App\Models\Header;
use App\Models\Media;
use App\Models\Page;
use App\Services\DynamicPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        protected DynamicPageService $dynamicPages,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $legacy = app(\App\Services\Content\LegacyRetirementService::class);
        if (! $legacy->adminUiEnabled() || (config('cms.admin.prefer_contents', true) && ! $request->boolean('legacy'))) {
            return redirect()->route('admin.contents.index', ['type' => 'page']);
        }

        $status = (string) $request->query('status', 'all');
        $q = trim((string) $request->query('q', ''));

        $counts = [
            'all' => Page::query()->count(),
            'publish' => Page::query()->where('status', 'publish')->count(),
            'draft' => Page::query()->where('status', 'draft')->count(),
            'private' => Page::query()->where('status', 'private')->count(),
            'trash' => Page::onlyTrashed()->count(),
        ];

        if ($status === 'trash') {
            $pagesQuery = Page::onlyTrashed()->with('author')->orderByDesc('deleted_at');
        } else {
            $pagesQuery = Page::query()
                ->with(['author', 'parent'])
                ->when($status !== 'all', fn ($query) => $query->where('status', $status))
                ->orderBy('title');
        }

        $pagesQuery->when($q !== '', function ($query) use ($q) {
            $query->where(function ($inner) use ($q) {
                $inner->where('title', 'like', '%'.$q.'%')
                    ->orWhere('slug', 'like', '%'.$q.'%');
            });
        });

        $pages = $pagesQuery->paginate(20)->withQueryString();

        return view('admin.pages.index', [
            'pages' => $pages,
            'status' => $status,
            'q' => $q,
            'counts' => $counts,
            'dynamicPages' => $this->dynamicPages->all(),
        ]);
    }

    public function create(): View|RedirectResponse
    {
        if (! app(\App\Services\Content\LegacyRetirementService::class)->adminUiEnabled()) {
            return redirect()->route('admin.contents.create', ['type' => 'page']);
        }

        return view('admin.pages.edit', $this->formData(new Page([
            'status' => 'draft',
            'template' => 'default',
            'header_mode' => 'master',
            'footer_mode' => 'master',
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['author_id'] = Auth::id();
        $page = Page::query()->create($data);
        $this->revision($page, $data, 'Page created');
        $this->dualWritePage($page);

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Page created.');
    }

    public function edit(int $page): View|RedirectResponse
    {
        if (! app(\App\Services\Content\LegacyRetirementService::class)->adminUiEnabled()) {
            return redirect()->route('admin.contents.index', ['type' => 'page']);
        }
        $page = Page::withTrashed()->findOrFail($page);

        return view('admin.pages.edit', $this->formData($page) + [
            'revisions' => $page->revisions()->limit(10)->get(),
        ]);
    }

    public function update(Request $request, int $page): RedirectResponse
    {
        $page = Page::withTrashed()->findOrFail($page);
        $data = $this->validated($request, $page);
        $page->update($data);
        $this->revision($page, $data, 'Page updated');
        $this->dualWritePage($page);

        return back()->with('success', 'Page saved.');
    }

    public function destroy(int $page): RedirectResponse
    {
        $page = Page::withTrashed()->findOrFail($page);

        if ($page->trashed()) {
            $page->forceDelete();

            return redirect()->route('admin.pages.index', ['status' => 'trash'])
                ->with('success', 'Page permanently deleted.');
        }

        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', 'Page moved to Trash.');
    }

    public function restore(int $page): RedirectResponse
    {
        $page = Page::onlyTrashed()->findOrFail($page);
        $page->restore();

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Page restored.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:trash,restore,delete,publish,draft'],
            'pages' => ['required', 'array', 'min:1'],
            'pages.*' => ['integer'],
        ]);

        $pages = Page::withTrashed()->whereIn('id', $data['pages'])->get();

        foreach ($pages as $page) {
            match ($data['action']) {
                'trash' => $page->trashed() ? null : $page->delete(),
                'restore' => $page->trashed() ? $page->restore() : null,
                'publish' => $page->update(['status' => 'publish', 'published_at' => $page->published_at ?: now()]),
                'draft' => $page->update(['status' => 'draft']),
                'delete' => $page->forceDelete(),
            };
        }

        return back()->with('success', 'Bulk action applied.');
    }

    public function duplicate(int $page): RedirectResponse
    {
        $page = Page::withTrashed()->findOrFail($page);
        $copy = $page->replicate(['slug', 'deleted_at']);
        $copy->title = $page->title.' (Copy)';
        $copy->slug = $page->slug.'-copy-'.now()->format('His');
        $copy->status = 'draft';
        $copy->published_at = null;
        $copy->author_id = Auth::id();
        $copy->save();
        $this->revision($copy, $copy->toArray(), 'Page duplicated');

        return redirect()->route('admin.pages.edit', $copy)->with('success', 'Draft copy created.');
    }

    public function preview(Page $page): View
    {
        return app(\App\Services\PageRendererService::class)->renderStatic($page);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(Page $page): array
    {
        return [
            'page' => $page,
            'headers' => Header::query()->orderBy('name')->get(),
            'footers' => Footer::query()->orderBy('name')->get(),
            'parents' => Page::query()
                ->when($page->exists, fn ($q) => $q->where('id', '!=', $page->id))
                ->orderBy('title')
                ->get(),
            'media' => Media::query()->latest()->limit(40)->get(),
            'templates' => $this->templates(),
            'reservedSlugs' => $this->dynamicPages->reservedSlugs(),
        ];
    }

    protected function validated(Request $request, ?Page $page = null): array
    {
        $reserved = $this->dynamicPages->reservedSlugs();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('pages', 'slug')->ignore($page?->id)->whereNull('deleted_at'),
                Rule::notIn($reserved),
            ],
            'parent_id' => ['nullable', 'exists:pages,id'],
            'content' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'publish', 'private'])],
            'featured_image_id' => ['nullable', 'exists:media,id'],
            'template' => ['required', Rule::in(array_keys($this->templates()))],
            'header_mode' => ['required', Rule::in(['master', 'custom', 'disable'])],
            'header_id' => ['nullable', 'exists:headers,id'],
            'footer_mode' => ['required', Rule::in(['master', 'custom', 'disable'])],
            'footer_id' => ['nullable', 'exists:footers,id'],
            'sidebar_position' => ['nullable', Rule::in(['none', 'left', 'right'])],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
            'seo_canonical' => ['nullable', 'url'],
            'seo_robots' => ['nullable', 'string', 'max:100'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string'],
            'og_type' => ['nullable', 'string', 'max:50'],
            'published_at' => ['nullable', 'date'],
            'custom_css' => ['nullable', 'string'],
            'custom_js' => ['nullable', 'string'],
            'custom_html' => ['nullable', 'string'],
        ], [
            'slug.not_in' => 'This slug is reserved for a Dynamic / system route. Choose another slug.',
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);
        if ($this->dynamicPages->isReservedSlug($data['slug'])) {
            throw ValidationException::withMessages([
                'slug' => 'This slug is reserved for a Dynamic / system route. Choose another slug.',
            ]);
        }

        if ($page && isset($data['parent_id']) && (int) $data['parent_id'] === (int) $page->id) {
            $data['parent_id'] = null;
        }

        if ($data['status'] === 'publish' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function templates(): array
    {
        return [
            'default' => 'Default',
            'full_width' => 'Full Width',
            'landing' => 'Landing Page',
            'no_header' => 'No Header',
            'no_footer' => 'No Footer',
            'blank' => 'Blank',
            'custom' => 'Custom',
        ];
    }

    protected function revision(Page $page, array $payload, string $note): void
    {
        ContentRevision::query()->create([
            'revisable_type' => Page::class,
            'revisable_id' => $page->id,
            'user_id' => Auth::id(),
            'payload' => $payload,
            'note' => $note,
        ]);
    }

    protected function dualWritePage(Page $page): void
    {
        try {
            app(\App\Services\Content\DualWriteContentSync::class)->syncPage($page->fresh());
        } catch (\Throwable) {
            // Dual-write must not break legacy admin if content types are not seeded yet.
        }
    }
}
