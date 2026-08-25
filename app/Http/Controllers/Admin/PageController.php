<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentRevision;
use App\Models\Footer;
use App\Models\Header;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.index', [
            'pages' => Page::query()->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.pages.edit', [
            'page' => new Page(['status' => 'draft', 'template' => 'default', 'header_mode' => 'master', 'footer_mode' => 'master']),
            'headers' => Header::query()->orderBy('name')->get(),
            'footers' => Footer::query()->orderBy('name')->get(),
            'templates' => $this->templates(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['author_id'] = Auth::id();
        $page = Page::query()->create($data);
        $this->revision($page, $data, 'Page created');

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Page created.');
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.edit', [
            'page' => $page,
            'headers' => Header::query()->orderBy('name')->get(),
            'footers' => Footer::query()->orderBy('name')->get(),
            'templates' => $this->templates(),
            'revisions' => $page->revisions()->limit(10)->get(),
        ]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $data = $this->validated($request, $page);
        $page->update($data);
        $this->revision($page, $data, 'Page updated');

        return back()->with('success', 'Page saved.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', 'Page deleted.');
    }

    public function preview(Page $page): View
    {
        return view('site.page', compact('page'));
    }

    protected function validated(Request $request, ?Page $page = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('pages', 'slug')->ignore($page?->id)],
            'content' => ['nullable', 'string'],
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
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);
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
