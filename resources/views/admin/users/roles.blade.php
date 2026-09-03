@extends('layouts.admin')

@section('title', 'Roles')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <p class="page-intro mb-0">Edit role capabilities like WordPress. Administrator always has every capability.</p>
    <a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">← All Users</a>
</div>

<div class="roles-layout">
    <aside class="panel roles-list">
        <div class="fw-semibold mb-2">Roles</div>
        @foreach ($roles as $role)
            <a href="{{ route('admin.users.roles', ['role' => $role->name]) }}" class="role-link {{ optional($selected)->id === $role->id ? 'is-active' : '' }}">
                <span>{{ $role->name }}</span>
                <span class="badge text-bg-secondary">{{ $role->users_count }}</span>
            </a>
        @endforeach
    </aside>

    <div class="panel">
        @if (! $selected)
            <div class="empty-state">No roles found. Re-run install sync.</div>
        @else
            <h2 class="h5 mb-2">{{ $selected->name }}</h2>
            <p class="page-intro mb-3">{{ $selected->description ?: 'Choose which capabilities this role should have.' }}</p>

            @if ($selected->name === 'Administrator')
                <div class="empty-state">Administrator has all capabilities and cannot be restricted.</div>
                <ul class="capability-list mt-3">
                    @foreach ($capabilities as $name => $label)
                        <li><span class="badge text-bg-success">✓</span> {{ $label }} <code>{{ $name }}</code></li>
                    @endforeach
                </ul>
            @else
                <form method="POST" action="{{ route('admin.users.roles.update', $selected) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label" for="description">Description</label>
                        <input id="description" class="form-control" name="description" value="{{ old('description', $selected->description) }}">
                    </div>
                    <div class="capability-grid mb-3">
                        @foreach ($capabilities as $name => $label)
                            <label class="capability-item">
                                <input type="checkbox" name="permissions[]" value="{{ $name }}" @checked($selected->hasPermissionTo($name))>
                                <span>
                                    <strong>{{ $label }}</strong>
                                    <small>{{ $name }}</small>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit">Save capabilities</button>
                    </div>
                </form>
            @endif
        @endif
    </div>
</div>
@endsection
