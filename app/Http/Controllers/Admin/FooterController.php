<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Footer;
use App\Services\LayoutBootstrapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FooterController extends Controller
{
    public function index(): View
    {
        return view('admin.footers.index', [
            'footers' => Footer::query()->orderByDesc('is_default')->orderBy('name')->get(),
        ]);
    }

    public function edit(Footer $footer): View
    {
        return view('admin.footers.builder', [
            'footer' => $footer,
            'structure' => $footer->editableStructure(),
            'available' => [
                'logo' => 'Logo',
                'text' => 'Text',
                'menu' => 'Menu',
                'social' => 'Social Links',
                'contact' => 'Contact',
                'newsletter' => 'Newsletter',
                'html' => 'HTML',
                'copyright' => 'Copyright',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $footer = Footer::query()->create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::random(4),
            'content' => Footer::defaultStructure(),
            'draft_content' => Footer::defaultStructure(),
            'status' => 'draft',
        ]);

        return redirect()->route('admin.footers.edit', $footer)->with('success', 'Footer created.');
    }

    public function update(Request $request, Footer $footer, LayoutBootstrapService $bootstrap): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'structure' => ['required', 'json'],
            'action' => ['required', 'in:draft,publish'],
        ]);

        $structure = json_decode($data['structure'], true);
        if (! is_array($structure)) {
            return back()->with('error', 'Invalid footer structure.');
        }

        $footer->name = $data['name'];

        if ($data['action'] === 'publish') {
            $footer->draft_content = $structure;
            $bootstrap->publishFooter($footer);
            $message = 'Footer published.';
        } else {
            $bootstrap->saveFooterDraft($footer, $structure);
            $message = 'Footer draft saved.';
        }

        return back()->with('success', $message);
    }

    public function destroy(Footer $footer): RedirectResponse
    {
        if ($footer->is_default) {
            return back()->with('error', 'Cannot delete the default footer.');
        }
        $footer->delete();

        return redirect()->route('admin.footers.index')->with('success', 'Footer deleted.');
    }
}
