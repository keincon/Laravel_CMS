<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Taxonomy;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaxonomyAdminController extends Controller
{
    public function index()
    {
        $this->authorizeManage();

        return view('admin.taxonomies.index', [
            'taxonomies' => Taxonomy::query()->withCount('terms')->orderBy('name')->get(),
        ]);
    }

    public function show(Taxonomy $taxonomy)
    {
        $this->authorizeManage();

        return view('admin.taxonomies.show', [
            'taxonomy' => $taxonomy,
            'terms' => $taxonomy->terms()->with('parent')->orderBy('name')->paginate(30),
            'parents' => $taxonomy->hierarchical
                ? $taxonomy->terms()->orderBy('name')->get()
                : collect(),
        ]);
    }

    public function storeTerm(Request $request, Taxonomy $taxonomy)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:terms,id'],
        ]);

        $slug = $data['slug'] ?: Str::slug($data['name']);
        $base = $slug;
        $i = 2;
        while (Term::query()->where('taxonomy_id', $taxonomy->id)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        Term::query()->create([
            'taxonomy_id' => $taxonomy->id,
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'parent_id' => $taxonomy->hierarchical ? ($data['parent_id'] ?? null) : null,
        ]);

        return back()->with('status', 'Term created.');
    }

    public function updateTerm(Request $request, Taxonomy $taxonomy, Term $term)
    {
        $this->authorizeManage();
        abort_unless((int) $term->taxonomy_id === (int) $taxonomy->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:terms,id'],
        ]);

        if (! empty($data['parent_id']) && (int) $data['parent_id'] === (int) $term->id) {
            return back()->withErrors(['parent_id' => 'A term cannot be its own parent.']);
        }

        $term->update([
            'name' => $data['name'],
            'slug' => $data['slug'] ?: $term->slug,
            'description' => $data['description'] ?? null,
            'parent_id' => $taxonomy->hierarchical ? ($data['parent_id'] ?? null) : null,
        ]);

        return back()->with('status', 'Term updated.');
    }

    public function destroyTerm(Taxonomy $taxonomy, Term $term)
    {
        $this->authorizeManage();
        abort_unless((int) $term->taxonomy_id === (int) $taxonomy->id, 404);
        $term->delete();

        return back()->with('status', 'Term deleted.');
    }

    private function authorizeManage(): void
    {
        abort_unless(auth()->user()?->can('manage_categories'), 403);
    }
}
