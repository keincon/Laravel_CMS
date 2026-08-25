<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TagController extends Controller
{
    public function index(): View
    {
        return view('admin.tags.index', [
            'tags' => Tag::query()->withCount('posts')->orderBy('name')->paginate(30),
        ]);
    }

    public function create(): View
    {
        return view('admin.tags.edit', [
            'tag' => new Tag,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tag = Tag::query()->create($this->validated($request));

        return redirect()->route('admin.tags.edit', $tag)->with('success', 'Tag created.');
    }

    public function edit(Tag $tag): View
    {
        return view('admin.tags.edit', [
            'tag' => $tag,
        ]);
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $tag->update($this->validated($request, $tag));

        return back()->with('success', 'Tag saved.');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->posts()->detach();
        $tag->delete();

        return redirect()->route('admin.tags.index')->with('success', 'Tag deleted.');
    }

    protected function validated(Request $request, ?Tag $tag = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('tags', 'slug')->ignore($tag?->id)],
            'description' => ['nullable', 'string'],
        ]);

        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);

        return $data;
    }
}
