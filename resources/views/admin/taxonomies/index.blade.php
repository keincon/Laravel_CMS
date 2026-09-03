@extends('layouts.admin')
@section('title', 'Taxonomies')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 mb-1">Taxonomies</h1>
        <p class="page-intro mb-0">Generic LaravelPress taxonomies (category, tag, custom).</p>
    </div>
</div>
<div class="panel">
    <table class="table mb-0">
        <thead><tr><th>Name</th><th>Slug</th><th>Hierarchical</th><th>Terms</th></tr></thead>
        <tbody>
            @foreach ($taxonomies as $taxonomy)
                <tr>
                    <td><a href="{{ route('admin.taxonomies.show', $taxonomy) }}">{{ $taxonomy->plural_label }}</a></td>
                    <td><code>{{ $taxonomy->slug }}</code></td>
                    <td>{{ $taxonomy->hierarchical ? 'Yes' : 'No' }}</td>
                    <td>{{ $taxonomy->terms_count }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
