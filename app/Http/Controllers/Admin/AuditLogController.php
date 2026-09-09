<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->string('q'));
        $action = trim((string) $request->string('action'));
        $userId = $request->integer('user_id') ?: null;

        $query = AuditLog::query()->with('user')->latest();

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->where('action', 'like', "%{$q}%")
                    ->orWhere('ip_address', 'like', "%{$q}%")
                    ->orWhere('entity_type', 'like', "%{$q}%")
                    ->orWhere('entity_id', 'like', "%{$q}%");
            });
        }

        if ($action !== '') {
            $query->where('action', $action);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $logs = $query->paginate(40)->withQueryString();

        $actions = AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $users = User::query()
            ->whereIn('id', AuditLog::query()->whereNotNull('user_id')->distinct()->pluck('user_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.audit-logs.index', [
            'logs' => $logs,
            'actions' => $actions,
            'users' => $users,
            'filters' => [
                'q' => $q,
                'action' => $action,
                'user_id' => $userId,
            ],
            'totalCount' => AuditLog::query()->count(),
        ]);
    }
}
