<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentRevision;
use App\Models\Footer;
use App\Models\Header;
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

    public function index(): View
    {
        $roots = Page::query()
            ->with(['children' => fn ($q) => $q->orderBy('title')])
            ->roots()
            ->orderBy('title')
            ->get();

        $ordered = collect();
        foreach ($roots as $root) {
            $ordered->push($root);
            foreach ($root->children as $child) {
                $ordered->push($child);
            }
        }

        // Include orphaned children whose parent was deleted hard / missing.
        $seen = $ordered->pluck('id')->all();
        $orphans = Page::query()->whereNotNull('parent_id')->whereNotIn('id', $seen)->orderBy('title')->get();
        $ordered = $ordered->concat($orphans);

        return view('admin.pages.index', [
            'pages' => $ordered,
            'dynamicPages' => $this->dynamicPages->all(),
        ]);
    }

    public function create(): View
    {
        return view('admin.pages.edit', [
            'page' => new Page(['status' => 'draft', 'template' => 'default', 'header_mode' => 'master', 'footer_mode' => 'master']),
            'headers' => Header::query()->orderBy('name')->get(),
            'footers' => Footer::query()->orderBy('name')->get(),
            'parents' => Page::query()->orderBy('title')->get(),
            'templates' => $this->templates(),
            'reservedSlugs' => $this->dynamicPages->reservedSlugs(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['author_id'] = Auth::id();
        $page = Page::query()->create($data);
        $this->revision($page, $data, 'Page created');

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Static page created.');
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.edit', [
            'page' => $page,
            'headers' => Header::query()->orderBy('name')->get(),
            'footers' => Footer::query()->orderBy('name')->get(),
            'parents' => Page::query()->where('id', '!=', $page->id)->orderBy('title')->get(),
            'templates' => $this->templates(),
            'revisions' => $page->revisions()->limit(10)->get(),
            'reservedSlugs' => $this->dynamicPages->reservedSlugs(),
        ]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $data = $this->validated($request, $page);
        $page->update($data);
        $this->revision($page, $data, 'Page updated');

        return back()->with('success', 'Static page saved.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', 'Static page deleted.');
    }

    public function preview(Page $page): View
    {
        return app(\App\Services\PageRendererService::class)->renderStatic($page);
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
            'status' => ['required', Rule::in(['draft', 'publish', 'private', 'trash'])],
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
}
