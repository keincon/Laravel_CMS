<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CmsSetting;
use App\Models\ContentRevision;
use App\Models\Footer;
use App\Models\Header;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $legacy = app(\App\Services\Content\LegacyRetirementService::class);
        if (! $legacy->adminUiEnabled() || (config('cms.admin.prefer_contents', true) && ! $request->boolean('legacy'))) {
            return redirect()->route('admin.contents.index', ['type' => 'post']);
        }

        $status = (string) $request->query('status', 'all');
        $q = trim((string) $request->query('q', ''));

        $counts = [
            'all' => Post::query()->notTrashed()->count(),
            'publish' => Post::query()->where('status', 'publish')->count(),
            'draft' => Post::query()->where('status', 'draft')->count(),
            'pending' => Post::query()->where('status', 'pending')->count(),
            'private' => Post::query()->where('status', 'private')->count(),
            'trash' => Post::query()->where('status', 'trash')->count(),
        ];

        $posts = Post::query()
            ->with(['author', 'categories'])
            ->when($status === 'all', fn ($query) => $query->notTrashed())
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('title', 'like', '%'.$q.'%')
                        ->orWhere('slug', 'like', '%'.$q.'%');
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.posts.index', [
            'posts' => $posts,
            'status' => $status,
            'q' => $q,
            'counts' => $counts,
        ]);
    }

    public function create(): View|RedirectResponse
    {
        if (! app(\App\Services\Content\LegacyRetirementService::class)->adminUiEnabled()) {
            return redirect()->route('admin.contents.create', ['type' => 'post']);
        }

        return view('admin.posts.edit', $this->formData(new Post([

            'status' => 'draft',
            'comment_status' => CmsSetting::getValue('default_comment_status', 'open'),
            'template' => 'default',
            'header_mode' => 'master',
            'footer_mode' => 'master',
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['author_id'] = Auth::id();
        $post = Post::query()->create($data);
        $post->categories()->sync($request->input('categories', []));
        $post->tags()->sync($request->input('tags', []));
        $this->revision($post, $data, 'Post created');
        $this->dualWritePost($post);

        return redirect()->route('admin.posts.edit', $post)->with('success', 'Post created.');
    }

    public function edit(Post $post): View|RedirectResponse
    {
        if (! app(\App\Services\Content\LegacyRetirementService::class)->adminUiEnabled()) {
            return redirect()->route('admin.contents.index', ['type' => 'post']);
        }

        return view('admin.posts.edit', $this->formData($post) + [
            'revisions' => $post->revisions()->limit(10)->get(),
        ]);
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $data = $this->validated($request, $post);
        $post->update($data);
        $post->categories()->sync($request->input('categories', []));
        $post->tags()->sync($request->input('tags', []));
        $this->revision($post, $data, 'Post updated');
        $this->dualWritePost($post);

        return back()->with('success', 'Post saved.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        if ($post->status !== 'trash') {
            $post->update(['status' => 'trash']);

            return redirect()->route('admin.posts.index')->with('success', 'Post moved to Trash.');
        }

        $post->delete();

        return redirect()->route('admin.posts.index', ['status' => 'trash'])->with('success', 'Post permanently deleted.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:trash,restore,delete,publish,draft'],
            'posts' => ['required', 'array', 'min:1'],
            'posts.*' => ['integer', 'exists:posts,id'],
        ]);

        $posts = Post::query()->whereIn('id', $data['posts'])->get();

        foreach ($posts as $post) {
            match ($data['action']) {
                'trash' => $post->update(['status' => 'trash']),
                'restore' => $post->update(['status' => 'draft']),
                'publish' => $post->update(['status' => 'publish', 'published_at' => $post->published_at ?: now()]),
                'draft' => $post->update(['status' => 'draft']),
                'delete' => $post->delete(),
            };
        }

        return back()->with('success', 'Bulk action applied.');
    }

    public function duplicate(Post $post): RedirectResponse
    {
        $copy = $post->replicate(['slug']);
        $copy->title = $post->title.' (Copy)';
        $copy->slug = $post->slug.'-copy-'.now()->format('His');
        $copy->status = 'draft';
        $copy->is_sticky = false;
        $copy->published_at = null;
        $copy->author_id = Auth::id();
        $copy->save();
        $copy->categories()->sync($post->categories()->pluck('categories.id'));
        $copy->tags()->sync($post->tags()->pluck('tags.id'));
        $this->revision($copy, $copy->toArray(), 'Post duplicated');

        return redirect()->route('admin.posts.edit', $copy)->with('success', 'Draft copy created.');
    }

    public function preview(Post $post): View
    {
        return app(\App\Services\PageRendererService::class)->renderPost($post->slug);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(Post $post): array
    {
        return [
            'post' => $post,
            'headers' => Header::query()->orderBy('name')->get(),
            'footers' => Footer::query()->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
            'tags' => Tag::query()->orderBy('name')->get(),
            'media' => Media::query()->latest()->limit(40)->get(),
            'templates' => $this->templates(),
            'selectedCategories' => $post->exists ? $post->categories()->pluck('categories.id')->all() : [],
            'selectedTags' => $post->exists ? $post->tags()->pluck('tags.id')->all() : [],
        ];
    }

    protected function validated(Request $request, ?Post $post = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('posts', 'slug')->ignore($post?->id)],
            'content' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['draft', 'pending', 'publish', 'private', 'trash'])],
            'is_sticky' => ['nullable', 'boolean'],
            'comment_status' => ['required', Rule::in(['open', 'closed'])],
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
            'categories' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
        ]);

        unset($data['categories'], $data['tags']);
        $data['is_sticky'] = $request->boolean('is_sticky');
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);
        if ($data['status'] === 'publish' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function templates(): array
    {
        return [
            'default' => 'Default Post',
            'full_width' => 'Full Width Post',
            'article' => 'Article',
            'video' => 'Video Post',
            'gallery' => 'Gallery Post',
            'custom' => 'Custom',
        ];
    }

    protected function revision(Post $post, array $payload, string $note): void
    {
        ContentRevision::query()->create([
            'revisable_type' => Post::class,
            'revisable_id' => $post->id,
            'user_id' => Auth::id(),
            'payload' => $payload,
            'note' => $note,
        ]);
    }

    protected function dualWritePost(Post $post): void
    {
        try {
            app(\App\Services\Content\DualWriteContentSync::class)->syncPost($post->fresh());
        } catch (\Throwable) {
            // Dual-write must not break legacy admin if content types are not seeded yet.
        }
    }
}
