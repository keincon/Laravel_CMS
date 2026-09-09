<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Services\MenuLinkTargetService;
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

        return redirect()->route('admin.menus.edit', $menu)->with('success', __('admin.menus.created'));
    }

    public function edit(Menu $menu, MenuLinkTargetService $linkTargets): View
    {
        $menu->load([
            'items' => fn ($q) => $q->whereNull('parent_id')->with(['children', 'page'])->orderBy('sort_order'),
        ]);

        return view('admin.menus.edit', [
            'menu' => $menu,
            'linkGroups' => $linkTargets->groups(),
            'parentOptions' => $menu->items()->whereNull('parent_id')->orderBy('sort_order')->get(['id', 'title']),
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

        return back()->with('success', __('admin.menus.saved'));
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();

        return redirect()->route('admin.menus.index')->with('success', __('admin.menus.deleted'));
    }

    public function storeItem(Request $request, Menu $menu): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:500'],
            'page_id' => ['nullable', 'exists:pages,id'],
            'parent_id' => ['nullable', 'exists:menu_items,id'],
        ]);

        if (! empty($data['parent_id'])) {
            $parent = MenuItem::query()->find($data['parent_id']);
            abort_unless($parent && $parent->menu_id === $menu->id && $parent->parent_id === null, 422);
        }

        [$url, $pageId] = $this->resolveLinkFields($data['url'] ?? null, $data['page_id'] ?? null);

        $max = (int) $menu->items()->max('sort_order');
        $menu->items()->create([
            'title' => $data['title'],
            'url' => $url,
            'page_id' => $pageId,
            'parent_id' => $data['parent_id'] ?: null,
            'sort_order' => $max + 1,
        ]);

        return back()->with('success', __('admin.menus.item_added'));
    }

    public function updateItem(Request $request, Menu $menu, MenuItem $item): RedirectResponse
    {
        abort_unless($item->menu_id === $menu->id, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:500'],
            'page_id' => ['nullable', 'exists:pages,id'],
            'parent_id' => ['nullable', 'exists:menu_items,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        if (! empty($data['parent_id'])) {
            abort_unless((int) $data['parent_id'] !== (int) $item->id, 422);
            $parent = MenuItem::query()->find($data['parent_id']);
            abort_unless($parent && $parent->menu_id === $menu->id && $parent->parent_id === null, 422);
            // Top-level items that have children cannot become nested in this simple editor.
            if ($item->children()->exists()) {
                $data['parent_id'] = null;
            }
        }

        [$url, $pageId] = $this->resolveLinkFields($data['url'] ?? null, $data['page_id'] ?? null);

        $item->update([
            'title' => $data['title'],
            'url' => $url,
            'page_id' => $pageId,
            'parent_id' => array_key_exists('parent_id', $data) ? ($data['parent_id'] ?: null) : $item->parent_id,
            'sort_order' => $data['sort_order'] ?? $item->sort_order,
        ]);

        return back()->with('success', __('admin.menus.item_updated'));
    }

    public function destroyItem(Menu $menu, MenuItem $item): RedirectResponse
    {
        abort_unless($item->menu_id === $menu->id, 404);
        $item->children()->update(['parent_id' => $item->parent_id]);
        $item->delete();

        return back()->with('success', __('admin.menus.item_deleted'));
    }

    /**
     * Prefer an explicit page link when page_id is set and URL is empty;
     * if both are set, keep URL (front-end prefers it) but still store page_id.
     *
     * @return array{0: ?string, 1: ?int}
     */
    private function resolveLinkFields(?string $url, mixed $pageId): array
    {
        $url = filled($url) ? trim((string) $url) : null;
        $pageId = filled($pageId) ? (int) $pageId : null;

        return [$url ?: null, $pageId];
    }
}
