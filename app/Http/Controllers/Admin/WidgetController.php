<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sidebar;
use App\Models\Widget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WidgetController extends Controller
{
    public function index(): View
    {
        $sidebar = Sidebar::main()->load('widgets');

        return view('admin.appearance.widgets', [
            'sidebar' => $sidebar,
            'types' => Widget::types(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $sidebar = Sidebar::main();
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Widget::types()))],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = match ($data['type']) {
            'recent_posts', 'categories' => ['limit' => 5],
            'text', 'custom_html' => ['content' => ''],
            default => [],
        };

        Widget::query()->create([
            'sidebar_id' => $sidebar->id,
            'type' => $data['type'],
            'title' => $data['title'] ?: (Widget::types()[$data['type']] ?? 'Widget'),
            'settings' => $settings,
            'sort_order' => ((int) $sidebar->widgets()->max('sort_order')) + 1,
            'is_active' => true,
        ]);

        return back()->with('success', 'Widget added.');
    }

    public function update(Request $request, Widget $widget): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'settings' => ['nullable', 'array'],
            'settings.limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'settings.content' => ['nullable', 'string'],
        ]);

        $widget->update([
            'title' => $data['title'] ?? $widget->title,
            'is_active' => $request->boolean('is_active'),
            'settings' => array_merge($widget->settings ?? [], $data['settings'] ?? []),
        ]);

        return back()->with('success', 'Widget saved.');
    }

    public function destroy(Widget $widget): RedirectResponse
    {
        $widget->delete();

        return back()->with('success', 'Widget removed.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:widgets,id'],
        ]);

        foreach (array_values($data['order']) as $index => $id) {
            Widget::query()->where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return back()->with('success', 'Widget order saved.');
    }
}
