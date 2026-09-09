@extends('layouts.admin')

@section('title', $content->exists ? __('admin.contents.edit').' '.$type->singular_label : __('admin.contents.new').' '.$type->singular_label)

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">{{ $content->exists ? __('admin.contents.edit') : __('admin.contents.add') }} {{ $type->singular_label }}</h1>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if ($content->exists && filled($content->slug))
            <a class="btn btn-outline-primary" href="{{ url('/'.$content->slug) }}" target="_blank" rel="noopener">
                {{ __('admin.contents.view_page') }}
            </a>
        @endif
        <a class="btn btn-outline-secondary" href="{{ route('admin.contents.index', ['type' => $type->slug]) }}">{{ __('admin.contents.back') }}</a>
    </div>
</div>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
    @if (! empty($isPageType))
        <x-admin.help-next context="pages_after_save" />
    @elseif (($type->slug ?? '') === 'post')
        <x-admin.help-next context="posts_after_save" />
    @elseif (($type->slug ?? '') === 'campaign')
        <x-admin.help-next context="campaigns_after_save" />
    @endif
@elseif (! empty($isPageType))
    <x-admin.help-next context="pages_edit" />
@elseif (($type->slug ?? '') === 'post')
    <x-admin.help-next context="posts_edit" />
@elseif (($type->slug ?? '') === 'campaign')
    <x-admin.help-next context="campaigns_edit" />
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
                <div class="input-group">
                    @if (! empty($isPageType))
                        <span class="input-group-text">/</span>
                    @endif
                    <input class="form-control" name="slug" id="content-slug" value="{{ old('slug', $content->slug) }}" @if (! empty($isPageType)) placeholder="about" @endif>
                </div>
                @if (! empty($isPageType))
                    <div class="form-text" id="public-url-hint">
                        {{ __('admin.contents.public_url') }}:
                        <code id="public-url-preview">{{ $publicPath ?? ('/'.ltrim((string) old('slug', $content->slug ?: '…'), '/')) }}</code>
                    </div>
                @endif

                <label class="form-label mt-3">{{ __('admin.contents.content') }}</label>
                <p class="form-text mb-2">{!! __('admin.contents.content_help') !!}</p>

                @php
                    $editorBlocks = old('blocks');
                    if (! is_array($editorBlocks)) {
                        $editorBlocks = $content->editorBlocks();
                    }
                    $hydratedFromHtml = old('blocks') === null && $content->exists && $content->hasHtmlBodyWithoutBlocks();
                @endphp

                @if ($hydratedFromHtml)
                    <div class="alert alert-info d-flex flex-column gap-1 mb-3" role="status">
                        <strong>{{ __('admin.contents.html_imported_title') }}</strong>
                        <span>{!! __('admin.contents.html_imported_help') !!}</span>
                    </div>
                @endif

                <div class="small text-muted mb-2">
                    <div class="fw-semibold text-body mb-1">{{ __('admin.contents.where_to_edit') }}</div>
                    <ol class="mb-0 ps-3">
                        <li>{!! __('admin.contents.where_to_edit_blocks') !!}</li>
                        <li>{!! __('admin.contents.where_to_edit_raw') !!}</li>
                    </ol>
                </div>

                <script type="application/json" id="blocks_json_data">@json($editorBlocks)</script>
                <input type="hidden" name="blocks_json" id="blocks_json" value="">
                <div id="block-editor-root"
                     class="mb-3"
                     data-media-json-url="{{ route('admin.media.json') }}"
                     data-i18n-html-hint="{{ __('admin.contents.html_block_hint') }}"
                     data-i18n-html-preview="{{ __('admin.contents.html_live_preview') }}"
                     data-i18n-empty-title="{{ __('admin.contents.empty_title') }}"
                     data-i18n-empty-help="{{ __('admin.contents.empty_help') }}"
                     data-i18n-preview-empty="{{ __('admin.contents.preview_empty') }}"
                     data-i18n-custom-html="{{ __('admin.contents.block_custom_html') }}"
                     data-i18n-preview-btn="{{ __('admin.contents.preview_btn') }}"
                     data-i18n-back-editor="{{ __('admin.contents.back_to_editor') }}"></div>

                <details class="mt-3 border rounded p-3 bg-light">
                    <summary class="form-label mb-0" style="cursor:pointer">{{ __('admin.contents.advanced_html') }}</summary>
                    <p class="form-text mt-2 mb-2">{{ __('admin.contents.advanced_html_help') }}</p>
                    <textarea class="form-control font-monospace" name="body" rows="10" spellcheck="false">{{ old('body', $content->body) }}</textarea>
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
            @if (! empty($isPageType))
                <div class="panel mb-3 border-primary">
                    <h2 class="h6 mb-2">{{ __('admin.contents.page_setup') }}</h2>
                    <p class="small text-muted mb-3">{{ __('admin.contents.page_setup_help') }}</p>

                    <label class="form-label">{{ __('admin.contents.status') }}</label>
                    <select class="form-select" name="status" id="content-status">
                        @foreach ([
                            'draft' => __('admin.contents.draft'),
                            'published' => __('admin.contents.published'),
                            'pending' => __('admin.contents.pending'),
                            'private' => __('admin.contents.private'),
                            'scheduled' => __('admin.contents.scheduled'),
                            'trash' => __('admin.contents.trash'),
                        ] as $st => $stLabel)
                            <option value="{{ $st }}" @selected(old('status', $content->status instanceof \BackedEnum ? $content->status->value : ($content->status ?: 'draft')) === $st)>{{ $stLabel }}</option>
                        @endforeach
                    </select>

                    <label class="form-label mt-3">{{ __('admin.contents.template') }}</label>
                    <select class="form-select" name="template">
                        @foreach ($pageTemplates as $key => $label)
                            <option value="{{ $key }}" @selected(old('template', $content->template ?: 'default') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">{{ __('admin.contents.template_help') }}</div>

                    @if ($type->hierarchical)
                        <label class="form-label mt-3">{{ __('admin.contents.parent') }}</label>
                        <select class="form-select" name="parent_id">
                            <option value="">{{ __('admin.contents.none_parent') }}</option>
                            @foreach ($parents as $parent)
                                <option value="{{ $parent->id }}" @selected((int) old('parent_id', $content->parent_id) === $parent->id)>{{ $parent->title }}</option>
                            @endforeach
                        </select>
                    @endif

                    <label class="form-label mt-3">{{ __('admin.contents.add_to_menu') }}</label>
                    <select class="form-select" name="add_to_menu_id">
                        <option value="">{{ __('admin.contents.add_to_menu_none') }}</option>
                        @foreach ($menus as $menu)
                            <option value="{{ $menu->id }}">{{ $menu->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">{{ __('admin.contents.add_to_menu_help') }}</div>

                    @if (($inMenus ?? collect())->isNotEmpty())
                        <div class="small mt-2">
                            {{ __('admin.contents.already_in_menus') }}:
                            @foreach ($inMenus as $menu)
                                <a href="{{ route('admin.menus.edit', $menu) }}">{{ $menu->name }}</a>@if (! $loop->last), @endif
                            @endforeach
                        </div>
                    @endif

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

                    <div class="d-grid gap-2 mt-3">
                        <button class="btn btn-outline-secondary" type="submit" name="action" value="draft" id="save-draft">{{ __('admin.contents.save_draft') }}</button>
                        <button class="btn btn-primary" type="submit" name="action" value="publish" id="publish-content">{{ __('admin.contents.publish') }}</button>
                        @if ($content->exists)
                            <button class="btn btn-outline-danger" type="submit" name="action" value="trash">{{ __('admin.contents.move_to_trash') }}</button>
                        @endif
                    </div>

                    <details class="mt-3">
                        <summary class="small text-muted" style="cursor:pointer">{{ __('admin.contents.advanced_options') }}</summary>
                        <label class="form-label mt-2">{{ __('admin.contents.schedule_at') }}</label>
                        <input class="form-control" type="datetime-local" name="scheduled_at"
                               value="{{ old('scheduled_at', optional($content->scheduled_at)->format('Y-m-d\\TH:i')) }}">
                        @if ($type->supports('comments'))
                            <label class="form-label mt-3">{{ __('admin.contents.discussion') }}</label>
                            <select class="form-select" name="comment_status">
                                <option value="open" @selected(old('comment_status', $content->comment_status ?: 'open') === 'open')>{{ __('admin.contents.open') }}</option>
                                <option value="closed" @selected(old('comment_status', $content->comment_status) === 'closed')>{{ __('admin.contents.closed') }}</option>
                            </select>
                        @endif
                    </details>
                </div>
            @else
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
            @endif
        </div>
    </div>
</form>
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('css/admin-editor.css') }}?v={{ @filemtime(public_path('css/admin-editor.css')) ?: time() }}">
    <script src="{{ asset('js/laravelpress-editor.js') }}?v={{ @filemtime(public_path('js/laravelpress-editor.js')) ?: time() }}" defer></script>
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

  const slugInput = document.getElementById('content-slug');
  const preview = document.getElementById('public-url-preview');
  if (slugInput && preview) {
    const sync = () => {
      const slug = (slugInput.value || '').replace(/^\/+/, '').trim() || '…';
      preview.textContent = '/' + slug;
    };
    slugInput.addEventListener('input', sync);
    sync();
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
