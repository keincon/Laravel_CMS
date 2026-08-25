<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Header;
use App\Services\LayoutBootstrapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HeaderController extends Controller
{
    public function index(): View
    {
        return view('admin.headers.index', [
            'headers' => Header::query()->orderByDesc('is_default')->orderBy('name')->get(),
        ]);
    }

    public function edit(Header $header): View
    {
        return view('admin.headers.builder', [
            'header' => $header,
            'structure' => $header->editableStructure(),
            'available' => [
                'logo' => 'Logo',
                'navigation' => 'Navigation',
                'search' => 'Search',
                'button' => 'Button',
                'social' => 'Social Links',
                'language' => 'Language Selector',
                'login' => 'Login',
                'text' => 'Text',
                'html' => 'Custom HTML',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $header = Header::query()->create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::random(4),
            'content' => Header::defaultStructure(),
            'draft_content' => Header::defaultStructure(),
            'status' => 'draft',
        ]);

        return redirect()->route('admin.headers.edit', $header)->with('success', 'Header created.');
    }

    public function update(Request $request, Header $header, LayoutBootstrapService $bootstrap): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'structure' => ['required', 'json'],
            'action' => ['required', 'in:draft,publish'],
        ]);

        $structure = json_decode($data['structure'], true);
        if (! is_array($structure)) {
            return back()->with('error', 'Invalid header structure.');
        }

        $header->name = $data['name'];

        if ($data['action'] === 'publish') {
            $header->draft_content = $structure;
            $bootstrap->publishHeader($header);
            $message = 'Header published.';
        } else {
            $bootstrap->saveHeaderDraft($header, $structure);
            $message = 'Header draft saved.';
        }

        return back()->with('success', $message);
    }

    public function destroy(Header $header): RedirectResponse
    {
        if ($header->is_default) {
            return back()->with('error', 'Cannot delete the default header.');
        }
        $header->delete();

        return redirect()->route('admin.headers.index')->with('success', 'Header deleted.');
    }
}
