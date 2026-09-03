@extends('layouts.admin')

@section('title', $type->plural_label)

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">{{ $type->plural_label }}</h1>
        <p class="page-intro mb-0">LaravelPress generic content ({{ $type->slug }}).</p>
    </div>
    <a class="btn btn-primary" href="{{ route('admin.contents.create', ['type' => $type->slug]) }}">Add New</a>
</div>

<div class="users-role-tabs mb-3">
    @foreach ($types as $t)
        <a href="{{ route('admin.contents.index', ['type' => $t->slug]) }}" class="{{ $type->slug === $t->slug ? 'is-active' : '' }}">{{ $t->plural_label }}</a>
    @endforeach
</div>

<div class="users-role-tabs mb-3">
    @foreach (['all' => 'All', 'published' => 'Published', 'draft' => 'Draft', 'pending' => 'Pending', 'scheduled' => 'Scheduled', 'trash' => 'Trash'] as $key => $label)
        <a href="{{ route('admin.contents.index', array_filter(['type' => $type->slug, 'status' => $key === 'all' ? null : $key, 'q' => $q ?: null])) }}"
           class="{{ $status === $key ? 'is-active' : '' }}">
            {{ $label }} <span>({{ $counts[$key] ?? 0 }})</span>
        </a>
    @endforeach
</div>

<div class="panel mb-3">
    <form method="GET" action="{{ route('admin.contents.index') }}" class="d-flex gap-2 flex-wrap align-items-center">
        <input type="hidden" name="type" value="{{ $type->slug }}">
        @if ($status !== 'all')
            <input type="hidden" name="status" value="{{ $status }}">
        @endif
        <input type="search" name="q" value="{{ $q }}" class="form-control" style="max-width:280px" placeholder="Search">
        <button class="btn btn-outline-secondary" type="submit">Search</button>
    </form>
</div>

<div class="panel">
    @if ($contents->isEmpty())
        <div class="empty-state">No content found. <a href="{{ route('admin.contents.create', ['type' => $type->slug]) }}">Create one</a>.</div>
    @else
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Status</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($contents as $item)
                        <tr>
                            <td><a class="fw-semibold" href="{{ route('admin.contents.edit', $item) }}">{{ $item->title }}</a></td>
                            <td>{{ $item->author?->publicName() ?? '—' }}</td>
                            <td>{{ $item->status instanceof \BackedEnum ? $item->status->value : $item->status }}</td>
                            <td>{{ $item->updated_at?->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $contents->links() }}</div>
    @endif
</div>
@endsection
