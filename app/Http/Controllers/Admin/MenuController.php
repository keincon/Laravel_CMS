<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(): View
    {
        return view('admin.menus.index', [
            'menus' => Menu::query()->withCount('items')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:menus,slug'],
            'location' => ['nullable', 'string', 'max:100'],
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);

        $menu = Menu::query()->create($data);

        return redirect()->route('admin.menus.edit', $menu)->with('success', 'Menu created.');
    }

    public function edit(Menu $menu): View
    {
        $menu->load(['items' => fn ($q) => $q->whereNull('parent_id')->with('children')]);

        return view('admin.menus.edit', [
            'menu' => $menu,
            'pages' => Page::query()->orderBy('title')->get(['id', 'title', 'slug']),
        ]);
    }

    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('menus', 'slug')->ignore($menu->id)],
            'location' => ['nullable', 'string', 'max:100'],
        ]);

        $menu->update($data);

        return back()->with('success', 'Menu saved.');
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();

        return redirect()->route('admin.menus.index')->with('success', 'Menu deleted.');
    }

    public function storeItem(Request $request, Menu $menu): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:500'],
            'page_id' => ['nullable', 'exists:pages,id'],
            'parent_id' => ['nullable', 'exists:menu_items,id'],
        ]);

        $max = (int) $menu->items()->max('sort_order');
        $menu->items()->create([
            'title' => $data['title'],
            'url' => $data['url'] ?: null,
            'page_id' => $data['page_id'] ?: null,
            'parent_id' => $data['parent_id'] ?: null,
            'sort_order' => $max + 1,
        ]);

        return back()->with('success', 'Menu item added.');
    }

    public function updateItem(Request $request, Menu $menu, MenuItem $item): RedirectResponse
    {
        abort_unless($item->menu_id === $menu->id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:500'],
            'page_id' => ['nullable', 'exists:pages,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $item->update([
            'title' => $data['title'],
            'url' => $data['url'] ?: null,
            'page_id' => $data['page_id'] ?: null,
            'sort_order' => $data['sort_order'] ?? $item->sort_order,
        ]);

        return back()->with('success', 'Menu item updated.');
    }

    public function destroyItem(Menu $menu, MenuItem $item): RedirectResponse
    {
        abort_unless($item->menu_id === $menu->id, 404);
        $item->children()->update(['parent_id' => $item->parent_id]);
        $item->delete();

        return back()->with('success', 'Menu item deleted.');
    }
}
