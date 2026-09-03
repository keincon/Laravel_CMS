<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Modules\ModuleManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleAdminController extends Controller
{
    public function index(ModuleManager $modules): View
    {
        return view('admin.modules.index', [
            'modules' => $modules->discover(),
        ]);
    }

    public function update(Request $request, string $module, ModuleManager $modules): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $modules->setEnabled($module, (bool) $data['enabled']);

        return back()->with('success', "Module [{$module}] ".($data['enabled'] ? 'enabled' : 'disabled').'.');
    }
}
