@extends('layouts.admin')

@section('title', __('admin.nav.audit_log'))

@section('content')
    <div class="admin-page-header">
        <h1>{{ __('admin.audit.title') }}</h1>
        <p class="muted">{{ __('admin.audit.intro') }}</p>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>When</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td>{{ $log->created_at?->toDateTimeString() }}</td>
                        <td>{{ $log->user?->publicName() ?? '—' }}</td>
                        <td><code>{{ $log->action }}</code></td>
                        <td>
                            @if ($log->entity_type)
                                {{ class_basename($log->entity_type) }} #{{ $log->entity_id }}
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $log->ip_address ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No audit events yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $logs->links() }}
@endsection
