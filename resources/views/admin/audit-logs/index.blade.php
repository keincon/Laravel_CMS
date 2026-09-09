@extends('layouts.admin')

@section('title', __('admin.audit.title'))

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">{{ __('admin.audit.title') }}</h1>
        <p class="page-intro mb-0">{{ __('admin.audit.intro') }}</p>
    </div>
</div>

<div class="alert alert-info small mb-3" role="status">
    {!! __('admin.audit.help_html', [
        'general' => route('admin.settings.general'),
        'posts' => route('admin.contents.index', ['type' => 'post']),
    ]) !!}
</div>

<form method="GET" action="{{ route('admin.audit-logs.index') }}" class="panel mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label" for="audit-q">{{ __('admin.audit.filter_q') }}</label>
            <input id="audit-q" type="search" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="{{ __('admin.audit.filter_q_placeholder') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="audit-action">{{ __('admin.audit.filter_action') }}</label>
            <select id="audit-action" name="action" class="form-select">
                <option value="">{{ __('admin.audit.filter_all_actions') }}</option>
                @foreach ($actions as $actionOption)
                    <option value="{{ $actionOption }}" @selected($filters['action'] === $actionOption)>{{ $actionOption }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="audit-user">{{ __('admin.audit.filter_user') }}</label>
            <select id="audit-user" name="user_id" class="form-select">
                <option value="">{{ __('admin.audit.filter_all_users') }}</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected((int) $filters['user_id'] === (int) $user->id)>
                        {{ $user->publicName() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1">{{ __('admin.audit.filter_apply') }}</button>
            <a class="btn btn-outline-secondary" href="{{ route('admin.audit-logs.index') }}">{{ __('admin.audit.filter_reset') }}</a>
        </div>
    </div>
</form>

@if ($totalCount === 0)
    <div class="panel">
        <div class="empty-state py-5 text-center">
            <h2 class="h5 mb-2">{{ __('admin.audit.empty_title') }}</h2>
            <p class="text-muted mb-3 mx-auto" style="max-width:36rem">{{ __('admin.audit.empty_body') }}</p>
            <div class="d-flex flex-wrap gap-2 justify-content-center">
                <a class="btn btn-primary" href="{{ route('admin.contents.index', ['type' => 'post']) }}">{{ __('admin.audit.empty_cta_posts') }}</a>
                <a class="btn btn-outline-secondary" href="{{ route('admin.settings.general') }}">{{ __('admin.audit.empty_cta_general') }}</a>
            </div>
        </div>
    </div>
@else
    <div class="admin-table-wrap panel p-0 overflow-hidden">
        <table class="admin-table mb-0">
            <thead>
                <tr>
                    <th>{{ __('admin.audit.col_when') }}</th>
                    <th>{{ __('admin.audit.col_user') }}</th>
                    <th>{{ __('admin.audit.col_action') }}</th>
                    <th>{{ __('admin.audit.col_entity') }}</th>
                    <th>{{ __('admin.audit.col_ip') }}</th>
                    <th>{{ __('admin.audit.col_details') }}</th>
                </tr>
            </thead>
                @forelse ($logs as $log)
                    @php
                        $hasDetails = filled($log->old_values) || filled($log->new_values) || filled($log->user_agent);
                    @endphp
                    <tbody x-data="{ open: false }">
                    <tr>
                        <td class="text-nowrap">{{ $log->created_at?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $log->user?->publicName() ?? '—' }}</td>
                        <td><code>{{ $log->action }}</code></td>
                        <td>
                            @if ($log->entity_type)
                                <span class="text-nowrap">{{ class_basename($log->entity_type) }} #{{ $log->entity_id }}</span>
                                @if (is_array($log->new_values) && ! empty($log->new_values['title']))
                                    <div class="small text-muted">{{ \Illuminate\Support\Str::limit((string) $log->new_values['title'], 48) }}</div>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-nowrap">{{ $log->ip_address ?: '—' }}</td>
                        <td>
                            @if ($hasDetails)
                                <button type="button" class="btn btn-sm btn-outline-secondary" @click="open = !open" x-text="open ? @js(__('admin.audit.hide_details')) : @js(__('admin.audit.view_details'))"></button>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                    @if ($hasDetails)
                        <tr x-show="open" x-cloak>
                            <td colspan="6" class="bg-light">
                                <div class="row g-3 p-2 small">
                                    @if (filled($log->old_values))
                                        <div class="col-md-4">
                                            <strong>{{ __('admin.audit.old_values') }}</strong>
                                            <pre class="mb-0 mt-1 p-2 border rounded bg-white" style="max-height:12rem;overflow:auto">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    @endif
                                    @if (filled($log->new_values))
                                        <div class="col-md-4">
                                            <strong>{{ __('admin.audit.new_values') }}</strong>
                                            <pre class="mb-0 mt-1 p-2 border rounded bg-white" style="max-height:12rem;overflow:auto">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    @endif
                                    @if (filled($log->user_agent))
                                        <div class="col-md-4">
                                            <strong>{{ __('admin.audit.user_agent') }}</strong>
                                            <p class="mb-0 mt-1 text-break">{{ $log->user_agent }}</p>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endif
                    </tbody>
                @empty
                    <tbody>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state py-4">{{ __('admin.audit.no_matches') }}</div>
                        </td>
                    </tr>
                    </tbody>
                @endforelse
        </table>
    </div>

    <div class="mt-3">{{ $logs->links() }}</div>
@endif
@endsection
