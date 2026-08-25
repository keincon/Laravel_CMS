<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ContentRevision;
use App\Models\Footer;
use App\Models\Header;
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
    public function index(): View
    {
        return view('admin.posts.index', [
            'posts' => Post::query()->with('author')->latest()->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.posts.edit', [
            'post' => new Post(['status' => 'draft', 'template' => 'default', 'header_mode' => 'master', 'footer_mode' => 'master']),
            'headers' => Header::query()->orderBy('name')->get(),
            'footers' => Footer::query()->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
            'tags' => Tag::query()->orderBy('name')->get(),
            'templates' => $this->templates(),
            'selectedCategories' => [],
            'selectedTags' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['author_id'] = Auth::id();
        $post = Post::query()->create($data);
        $post->categories()->sync($request->input('categories', []));
        $post->tags()->sync($request->input('tags', []));
        $this->revision($post, $data, 'Post created');

        return redirect()->route('admin.posts.edit', $post)->with('success', 'Post created.');
    }

    public function edit(Post $post): View
    {
        return view('admin.posts.edit', [
            'post' => $post,
            'headers' => Header::query()->orderBy('name')->get(),
            'footers' => Footer::query()->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
            'tags' => Tag::query()->orderBy('name')->get(),
            'templates' => $this->templates(),
            'selectedCategories' => $post->categories()->pluck('categories.id')->all(),
            'selectedTags' => $post->tags()->pluck('tags.id')->all(),
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

        return back()->with('success', 'Post saved.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'Post deleted.');
    }

    public function preview(Post $post): View
    {
        return app(\App\Services\PageRendererService::class)->renderPost($post->slug);
    }

    protected function validated(Request $request, ?Post $post = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('posts', 'slug')->ignore($post?->id)],
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
            'categories' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
        ]);

        unset($data['categories'], $data['tags']);
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
}
