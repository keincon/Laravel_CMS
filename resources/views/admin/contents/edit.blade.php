@extends('layouts.admin')

@section('title', $content->exists ? __('admin.contents.edit').' '.$type->singular_label : __('admin.contents.new').' '.$type->singular_label)

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">{{ $content->exists ? __('admin.contents.edit') : __('admin.contents.add') }} {{ $type->singular_label }}</h1>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.contents.index', ['type' => $type->slug]) }}">{{ __('admin.contents.back') }}</a>
</div>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<form method="POST"
      action="{{ $content->exists ? route('admin.contents.update', $content) : route('admin.contents.store') }}"
      id="content-editor-form"
      data-unsaved-warn="1">
    @csrf
    @if ($content->exists)
        @method('PUT')
    @else
        <input type="hidden" name="type" value="{{ $type->slug }}">
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="panel mb-3">
                <label class="form-label">{{ __('admin.contents.title_label') }}</label>
                <input class="form-control form-control-lg" name="title" id="content-title" value="{{ old('title', $content->title) }}" required>
                @error('title') <div class="text-danger small">{{ $message }}</div> @enderror

                <label class="form-label mt-3">{{ __('admin.contents.slug') }}</label>
                <input class="form-control" name="slug" value="{{ old('slug', $content->slug) }}">

                <label class="form-label mt-3">{{ __('admin.contents.content') }}</label>
                <p class="form-text mb-2">{!! __('admin.contents.content_help') !!}</p>
                <input type="hidden" name="blocks_json" id="blocks_json" value='@json(old('blocks', $content->blocks ?? []))'>
                <div id="block-editor-root"
                     class="mb-3"
                     data-media-json-url="{{ route('admin.media.json') }}"></div>

                <details class="mt-3">
                    <summary class="form-label" style="cursor:pointer">{{ __('admin.contents.advanced_html') }}</summary>
                    <p class="form-text">{{ __('admin.contents.advanced_html_help') }}</p>
                    <textarea class="form-control mt-2" name="body" rows="6">{{ old('body', $content->body) }}</textarea>
                </details>

                @if ($type->supports('excerpt'))
                    <label class="form-label mt-3">{{ __('admin.contents.excerpt') }}</label>
                    <textarea class="form-control" name="excerpt" rows="3">{{ old('excerpt', $content->excerpt) }}</textarea>
                @endif
            </div>

            @isset($revisions)
                <div class="panel">
                    <h2 class="h6">{{ __('admin.contents.revisions') }}</h2>
                    @forelse ($revisions as $revision)
                        <div class="d-flex justify-content-between align-items-center small mb-2 gap-2">
                            <div class="text-muted">
                                #{{ $revision->revision_number }} — {{ $revision->created_at?->toDateTimeString() }}
                                by {{ $revision->user?->publicName() ?? __('admin.contents.by_system') }}
                                @if ($revision->note) ({{ $revision->note }}) @endif
                            </div>
                            <div class="d-flex gap-1">
                                @if ($loop->index === 0 && isset($revisions[1]))
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.contents.revisions.compare', ['content' => $content, 'left' => $revisions[1]->id, 'right' => $revision->id]) }}">{{ __('admin.contents.compare') }}</a>
                                @endif
                                <form method="POST" action="{{ route('admin.contents.revisions.restore', [$content, $revision]) }}" onsubmit="return confirm(@js(__('admin.contents.restore_confirm')))">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-primary" type="submit">{{ __('admin.contents.restore') }}</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="small text-muted">{{ __('admin.contents.no_revisions') }}</div>
                    @endforelse
                </div>
            @endisset
        </div>

        <div class="col-lg-4">
            <div class="panel mb-3">
                <label class="form-label">{{ __('admin.contents.status') }}</label>
                <select class="form-select" name="status" id="content-status">
                    @foreach ([
                        'draft' => __('admin.contents.draft'),
                        'pending' => __('admin.contents.pending'),
                        'private' => __('admin.contents.private'),
                        'scheduled' => __('admin.contents.scheduled'),
                        'published' => __('admin.contents.published'),
                        'trash' => __('admin.contents.trash'),
                    ] as $st => $stLabel)
                        <option value="{{ $st }}" @selected(old('status', $content->status instanceof \BackedEnum ? $content->status->value : ($content->status ?: 'draft')) === $st)>{{ $stLabel }}</option>
                    @endforeach
                </select>

                <label class="form-label mt-3">{{ __('admin.contents.schedule_at') }}</label>
                <input class="form-control" type="datetime-local" name="scheduled_at"
                       value="{{ old('scheduled_at', optional($content->scheduled_at)->format('Y-m-d\\TH:i')) }}">

                @if ($type->hierarchical)
                    <label class="form-label mt-3">{{ __('admin.contents.parent') }}</label>
                    <select class="form-select" name="parent_id">
                        <option value="">{{ __('admin.contents.none_parent') }}</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->id }}" @selected((int) old('parent_id', $content->parent_id) === $parent->id)>{{ $parent->title }}</option>
                        @endforeach
                    </select>
                @endif

                @if ($type->supports('comments'))
                    <label class="form-label mt-3">{{ __('admin.contents.discussion') }}</label>
                    <select class="form-select" name="comment_status">
                        <option value="open" @selected(old('comment_status', $content->comment_status ?: 'open') === 'open')>{{ __('admin.contents.open') }}</option>
                        <option value="closed" @selected(old('comment_status', $content->comment_status) === 'closed')>{{ __('admin.contents.closed') }}</option>
                    </select>
                @endif

                <label class="form-label mt-3">{{ __('admin.contents.template') }}</label>
                <input class="form-control" name="template" value="{{ old('template', $content->template) }}">

                @if ($type->supports('featured_image'))
                    <div class="mt-3">
                        <label class="form-label">{{ __('admin.contents.featured_image') }}</label>
                        <x-admin.media-picker
                            name="featured_media_id"
                            :value="old('featured_media_id', $content->featured_media_id)"
                            :label="__('admin.contents.select_featured_image')"
                        />
                    </div>
                @endif

                @foreach (($taxonomies ?? collect()) as $taxonomy)
                    <div class="mt-3">
                        <label class="form-label">{{ $taxonomy->plural_label ?: $taxonomy->name }}</label>
                        <div class="border rounded p-2" style="max-height:180px;overflow:auto">
                            @forelse ($taxonomy->terms as $term)
                                <label class="d-block small mb-1">
                                    <input type="checkbox"
                                           name="term_ids[]"
                                           value="{{ $term->id }}"
                                           @checked(in_array($term->id, $selectedTermIds ?? [], true))>
                                    {{ $term->name }}
                                </label>
                            @empty
                                <div class="text-muted small">{{ __('admin.contents.no_terms') }}</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach

                <div class="d-grid gap-2 mt-3">
                    <button class="btn btn-outline-secondary" type="submit" name="action" value="draft" id="save-draft">{{ __('admin.contents.save_draft') }}</button>
                    <button class="btn btn-primary" type="submit" name="action" value="publish" id="publish-content">{{ __('admin.contents.publish') }}</button>
                    @if ($content->exists)
                        <button class="btn btn-outline-danger" type="submit" name="action" value="trash">{{ __('admin.contents.move_to_trash') }}</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('css/admin-editor.css') }}">
    <script src="{{ asset('js/laravelpress-editor.js') }}" defer></script>
@endpush

@push('scripts')
<script>
(() => {
  const form = document.getElementById('content-editor-form');
  if (form) {
    let dirty = false;
    form.addEventListener('input', () => { dirty = true; });
    form.addEventListener('submit', () => { dirty = false; });
    window.addEventListener('beforeunload', (e) => {
      if (!dirty) return;
      e.preventDefault();
      e.returnValue = '';
    });
  }

  @if($content->exists)
  const autosaveUrl = @json(route('admin.contents.autosave', $content));
  setInterval(() => {
    const body = new FormData(form);
    body.append('blocks_json', document.getElementById('blocks_json')?.value || '[]');
    fetch(autosaveUrl, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body,
      credentials: 'same-origin',
    }).catch(() => {});
  }, 30000);
  @endif
})();
</script>
@endpush
