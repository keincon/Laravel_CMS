@extends('layouts.admin')

@section('title', __('admin.nav.modules'))

@section('content')
    <div class="admin-page-header">
        <h1>{{ __('admin.modules.title') }}</h1>
        <p class="muted">{!! __('admin.modules.intro') !!}</p>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Version</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($modules as $name => $module)
                    <tr>
                        <td><strong>{{ $name }}</strong></td>
                        <td>{{ $module['version'] ?? '—' }}</td>
                        <td>{{ $module['description'] ?? '—' }}</td>
                        <td>{{ !empty($module['enabled']) ? 'Enabled' : 'Disabled' }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.modules.update', $name) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="enabled" value="{{ empty($module['enabled']) ? 1 : 0 }}">
                                <button class="btn btn-sm {{ !empty($module['enabled']) ? 'btn-outline-danger' : 'btn-primary' }}" type="submit">
                                    {{ !empty($module['enabled']) ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">No modules discovered.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
