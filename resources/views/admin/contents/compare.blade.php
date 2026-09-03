@extends('layouts.admin')
@section('title', 'Compare Revisions')
@section('content')
<div class="mb-3">
    <h1 class="h3">Compare revisions</h1>
    <p class="page-intro"><a href="{{ route('admin.contents.edit', $content) }}">Back to editor</a></p>
</div>
<div class="row g-3">
    <div class="col-md-6">
        <div class="panel">
            <h2 class="h6">#{{ $left->revision_number }} — {{ $left->created_at }}</h2>
            <p><strong>{{ $left->title }}</strong></p>
            <pre class="small" style="white-space:pre-wrap">{{ $left->body ?? ($left->payload['body'] ?? '') }}</pre>
        </div>
    </div>
    <div class="col-md-6">
        <div class="panel">
            <h2 class="h6">#{{ $right->revision_number }} — {{ $right->created_at }}</h2>
            <p><strong>{{ $right->title }}</strong></p>
            <pre class="small" style="white-space:pre-wrap">{{ $right->body ?? ($right->payload['body'] ?? '') }}</pre>
        </div>
    </div>
</div>
@endsection
