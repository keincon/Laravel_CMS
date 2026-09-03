<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContentTypeAdminController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->can('manage_settings') || auth()->user()?->can('manage_posts'), 403);

        return view('admin.content-types.index', [
            'types' => ContentType::query()->orderBy('menu_position')->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->can('manage_settings'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:content_types,slug'],
            'singular_label' => ['required', 'string', 'max:100'],
            'plural_label' => ['required', 'string', 'max:100'],
            'hierarchical' => ['nullable', 'boolean'],
            'has_archive' => ['nullable', 'boolean'],
            'public' => ['nullable', 'boolean'],
            'show_in_rest' => ['nullable', 'boolean'],
            'supports' => ['nullable', 'array'],
            'supports.*' => ['string'],
        ]);

        ContentType::query()->create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?: Str::slug($data['name']),
            'singular_label' => $data['singular_label'],
            'plural_label' => $data['plural_label'],
            'hierarchical' => $request->boolean('hierarchical'),
            'has_archive' => $request->boolean('has_archive', true),
            'public' => $request->boolean('public', true),
            'show_in_rest' => $request->boolean('show_in_rest', true),
            'supports' => $data['supports'] ?? ['title', 'editor', 'excerpt', 'author', 'revisions'],
            'is_builtin' => false,
            'menu_position' => 30,
        ]);

        return back()->with('status', 'Content type created.');
    }

    public function update(Request $request, ContentType $contentType)
    {
        abort_unless(auth()->user()?->can('manage_settings'), 403);

        $data = $request->validate([
            'singular_label' => ['required', 'string', 'max:100'],
            'plural_label' => ['required', 'string', 'max:100'],
            'hierarchical' => ['nullable', 'boolean'],
            'has_archive' => ['nullable', 'boolean'],
            'public' => ['nullable', 'boolean'],
            'show_in_rest' => ['nullable', 'boolean'],
            'supports' => ['nullable', 'array'],
            'supports.*' => ['string'],
        ]);

        $contentType->update([
            'singular_label' => $data['singular_label'],
            'plural_label' => $data['plural_label'],
            'hierarchical' => $request->boolean('hierarchical'),
            'has_archive' => $request->boolean('has_archive'),
            'public' => $request->boolean('public'),
            'show_in_rest' => $request->boolean('show_in_rest'),
            'supports' => $data['supports'] ?? $contentType->supports,
        ]);

        return back()->with('status', 'Content type updated.');
    }

    public function destroy(ContentType $contentType)
    {
        abort_unless(auth()->user()?->can('manage_settings'), 403);

        if ($contentType->is_builtin) {
            return back()->withErrors(['type' => 'Built-in content types cannot be deleted.']);
        }

        if ($contentType->contents()->exists()) {
            return back()->withErrors(['type' => 'Content type has content and cannot be deleted.']);
        }

        $contentType->delete();

        return back()->with('status', 'Content type deleted.');
    }
}
