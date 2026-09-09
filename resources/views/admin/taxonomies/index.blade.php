@extends('layouts.admin')
@section('title', __('admin.nav.taxonomies'))
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-1">{{ __('admin.nav.taxonomies') }}</h1>
        <p class="page-intro mb-0">{{ __('admin.taxonomies.intro') }}</p>
    </div>
</div>
<div class="panel">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>{{ __('admin.taxonomies.col_name') }}</th>
                <th>{{ __('admin.taxonomies.col_slug') }}</th>
                <th>{{ __('admin.taxonomies.col_hierarchical') }}</th>
                <th>{{ __('admin.taxonomies.col_terms') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($taxonomies as $taxonomy)
                <tr>
                    <td><a href="{{ route('admin.taxonomies.show', $taxonomy) }}">{{ $taxonomy->displayPluralLabel() }}</a></td>
                    <td><code>{{ $taxonomy->slug }}</code></td>
                    <td>{{ $taxonomy->hierarchical ? __('admin.ui.yes') : __('admin.ui.no') }}</td>
                    <td>{{ $taxonomy->terms_count }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
