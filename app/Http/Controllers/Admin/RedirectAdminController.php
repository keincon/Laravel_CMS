<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Redirect;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RedirectAdminController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->can('manage_settings'), 403);

        return view('admin.redirects.index', [
            'redirects' => Redirect::query()->latest()->paginate(30),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()?->can('manage_settings'), 403);

        $data = $request->validate([
            'from_path' => ['required', 'string', 'max:255', 'unique:redirects,from_path'],
            'to_path' => ['required', 'string', 'max:255'],
            'status_code' => ['required', 'integer', Rule::in([301, 302, 307, 308])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Redirect::query()->create([
            'from_path' => $this->normalizePath($data['from_path']),
            'to_path' => $data['to_path'],
            'status_code' => $data['status_code'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('status', 'Redirect created.');
    }

    public function update(Request $request, Redirect $redirect)
    {
        abort_unless(auth()->user()?->can('manage_settings'), 403);

        $data = $request->validate([
            'from_path' => ['required', 'string', 'max:255', Rule::unique('redirects', 'from_path')->ignore($redirect->id)],
            'to_path' => ['required', 'string', 'max:255'],
            'status_code' => ['required', 'integer', Rule::in([301, 302, 307, 308])],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $redirect->update([
            'from_path' => $this->normalizePath($data['from_path']),
            'to_path' => $data['to_path'],
            'status_code' => $data['status_code'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('status', 'Redirect updated.');
    }

    public function destroy(Redirect $redirect)
    {
        abort_unless(auth()->user()?->can('manage_settings'), 403);
        $redirect->delete();

        return back()->with('status', 'Redirect deleted.');
    }

    private function normalizePath(string $path): string
    {
        $path = '/'.ltrim(trim($path), '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
