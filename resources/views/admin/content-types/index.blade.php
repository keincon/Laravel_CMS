@extends('layouts.admin')
@section('title', __('admin.nav.content_types'))
@section('content')
<div class="mb-3">
    <h1 class="h3 mb-1">{{ __('admin.nav.content_types') }}</h1>
    <p class="page-intro mb-0">{{ __('admin.content_types.intro') }}</p>
</div>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel">
            <h2 class="h6">{{ __('admin.content_types.add') }}</h2>
            <form method="POST" action="{{ route('admin.content-types.store') }}">
                @csrf
                <label class="form-label">{{ __('admin.ui.name') }}</label>
                <input class="form-control" name="name" required>
                <label class="form-label mt-2">{{ __('admin.ui.slug') }}</label>
                <input class="form-control" name="slug" placeholder="news">
                <label class="form-label mt-2">{{ __('admin.content_types.singular') }}</label>
                <input class="form-control" name="singular_label" required>
                <label class="form-label mt-2">{{ __('admin.content_types.plural') }}</label>
                <input class="form-control" name="plural_label" required>
                <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="hierarchical" value="1"><label class="form-check-label">{{ __('admin.content_types.hierarchical') }}</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="has_archive" value="1" checked><label class="form-check-label">{{ __('admin.content_types.has_archive') }}</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="public" value="1" checked><label class="form-check-label">{{ __('admin.content_types.public') }}</label></div>
                <div class="form-check"><input class="form-check-input" type="checkbox" name="show_in_rest" value="1" checked><label class="form-check-label">{{ __('admin.content_types.show_in_rest') }}</label></div>
                @foreach (['title','editor','excerpt','author','featured_image','comments','revisions','custom_fields','hierarchy','archive'] as $support)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="supports[]" value="{{ $support }}" @checked(in_array($support, ['title','editor','excerpt','author','revisions'], true))>
                        <label class="form-check-label">{{ $support }}</label>
                    </div>
                @endforeach
                <button class="btn btn-primary mt-3" type="submit">{{ __('admin.ui.create') }}</button>
            </form>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel">
            <table class="table mb-0">
                <thead><tr><th>{{ __('admin.ui.type') }}</th><th>{{ __('admin.ui.slug') }}</th><th>{{ __('admin.content_types.supports') }}</th><th></th></tr></thead>
                <tbody>
                    @foreach ($types as $type)
                        <tr>
                            <td>
                                {{ $type->plural_label }}
                                @if ($type->is_builtin) <span class="badge text-bg-secondary">{{ __('admin.content_types.builtin') }}</span> @endif
                            </td>
                            <td><code>{{ $type->slug }}</code></td>
                            <td class="small">{{ implode(', ', $type->supports ?? []) }}</td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.contents.index', ['type' => $type->slug]) }}">{{ __('admin.ui.open') }}</a>
                                @unless ($type->is_builtin)
                                    <form class="d-inline" method="POST" action="{{ route('admin.content-types.destroy', $type) }}" onsubmit="return confirm(@js(__('admin.content_types.confirm_delete')))">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">{{ __('admin.ui.delete') }}</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
