<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PageResource;
use App\Http\Resources\Api\V1\PostResource;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminContentController extends Controller
{
    public function storePost(Request $request)
    {
        $data = $this->validatedPost($request);
        $data['author_id'] = $request->user()->id;
        $post = Post::query()->create($data);

        return (new PostResource($post->load(['author', 'categories', 'tags'])))
            ->response()
            ->setStatusCode(201);
    }

    public function updatePost(Request $request, Post $post)
    {
        $post->update($this->validatedPost($request, $post));

        return new PostResource($post->fresh()->load(['author', 'categories', 'tags']));
    }

    public function destroyPost(Post $post)
    {
        $post->delete();

        return response()->json(['message' => 'Post deleted.']);
    }

    public function storePage(Request $request)
    {
        $data = $this->validatedPage($request);
        $data['author_id'] = $request->user()->id;
        $page = Page::query()->create($data);

        return (new PageResource($page))->response()->setStatusCode(201);
    }

    public function updatePage(Request $request, Page $page)
    {
        $page->update($this->validatedPage($request, $page));

        return new PageResource($page->fresh());
    }

    public function destroyPage(Page $page)
    {
        $page->delete();

        return response()->json(['message' => 'Page deleted.']);
    }

    protected function validatedPost(Request $request, ?Post $post = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('posts', 'slug')->ignore($post?->id)],
            'content' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['draft', 'publish', 'private', 'trash'])],
            'published_at' => ['nullable', 'date'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
            'seo_canonical' => ['nullable', 'url'],
            'seo_robots' => ['nullable', 'string', 'max:100'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string'],
            'og_type' => ['nullable', 'string', 'max:50'],
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['title']);
        $data['status'] = $data['status'] ?? 'draft';

        return $data;
    }

    protected function validatedPage(Request $request, ?Page $page = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('pages', 'slug')->ignore($page?->id)],
            'content' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['draft', 'publish', 'private', 'trash'])],
            'published_at' => ['nullable', 'date'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
            'seo_canonical' => ['nullable', 'url'],
            'seo_robots' => ['nullable', 'string', 'max:100'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string'],
            'og_type' => ['nullable', 'string', 'max:50'],
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['title']);
        $data['status'] = $data['status'] ?? 'draft';

        return $data;
    }
}
