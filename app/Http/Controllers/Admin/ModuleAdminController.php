<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Modules\ModuleManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class ModuleAdminController extends Controller
{
    public function index(ModuleManager $modules): View
    {
        $discovered = $modules->discover();
        ksort($discovered, SORT_NATURAL | SORT_FLAG_CASE);

        return view('admin.modules.index', [
            'modules' => $discovered,
        ]);
    }

    public function update(Request $request, string $module, ModuleManager $modules): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        try {
            $modules->setEnabled($module, (bool) $data['enabled']);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $message = $data['enabled']
            ? __('admin.modules.flash_enabled', ['name' => $module])
            : __('admin.modules.flash_disabled', ['name' => $module]);

        return back()->with('success', $message);
    }
}
