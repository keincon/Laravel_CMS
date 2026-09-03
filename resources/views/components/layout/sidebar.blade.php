@props(['context' => 'page'])

@php
    $sidebar = \App\Models\Sidebar::query()->where('slug', 'main')->with(['activeWidgets'])->first();
    $widgets = $sidebar?->activeWidgets ?? collect();
    $permalinks = app(\App\Services\PermalinkService::class);
    $registry = app(\App\Support\Widgets\WidgetRegistry::class);
    if ($registry->all() === []) {
        $registry->registerDefaults();
    }
@endphp

<aside class="cms-sidebar" style="position:sticky;top:1rem">
    @forelse ($widgets as $widget)
        @continue(! $registry->has($widget->type) && ! in_array($widget->type, ['text', 'custom_html'], true))
        <section class="cms-widget" style="margin-bottom:1.5rem" data-widget-type="{{ $widget->type }}">
            @if ($widget->title)
                <h3 style="margin:0 0 .75rem;font-size:1rem">{{ $widget->title }}</h3>
            @endif

            @switch($widget->type)
                @case('search')
                    <x-search-form />
                    @break

                @case('categories')
                    @php
                        $terms = \App\Models\Term::query()
                            ->whereHas('taxonomy', fn ($q) => $q->where('slug', 'category'))
                            ->orderBy('name')
                            ->limit((int) ($widget->settings['limit'] ?? 12))
                            ->get();
                        if ($terms->isEmpty()) {
                            $terms = \App\Models\Category::query()->orderBy('name')->limit((int) ($widget->settings['limit'] ?? 12))->get();
                        }
                    @endphp
                    <ul style="list-style:none;padding:0;margin:0">
                        @forelse ($terms as $category)
                            <li style="margin-bottom:.35rem"><a href="{{ url('/category/'.$category->slug) }}">{{ $category->name }}</a></li>
                        @empty
                            <li style="opacity:.7">No categories</li>
                        @endforelse
                    </ul>
                    @break

                @case('tags')
                    @php
                        $tags = \App\Models\Term::query()
                            ->whereHas('taxonomy', fn ($q) => $q->whereIn('slug', ['post_tag', 'tag']))
                            ->orderBy('name')
                            ->limit((int) ($widget->settings['limit'] ?? 20))
                            ->get();
                    @endphp
                    <ul style="list-style:none;padding:0;margin:0;display:flex;flex-wrap:wrap;gap:.35rem">
                        @forelse ($tags as $tag)
                            <li><a href="{{ url('/tag/'.$tag->slug) }}">#{{ $tag->name }}</a></li>
                        @empty
                            <li style="opacity:.7">No tags</li>
                        @endforelse
                    </ul>
                    @break

                @case('recent_posts')
                    @php
                        $limit = (int) ($widget->settings['limit'] ?? 5);
                        $recent = \App\Models\Content::query()
                            ->ofType('post')
                            ->published()
                            ->latest('published_at')
                            ->limit($limit)
                            ->get();
                        if ($recent->isEmpty()) {
                            $recent = \App\Models\Post::query()->published()->latest('published_at')->limit($limit)->get();
                        }
                    @endphp
                    <ul style="list-style:none;padding:0;margin:0">
                        @forelse ($recent as $post)
                            <li style="margin-bottom:.35rem">
                                <a href="{{ $post instanceof \App\Models\Content ? $permalinks->contentUrl($post) : $permalinks->postUrl($post) }}">{{ $post->title }}</a>
                            </li>
                        @empty
                            <li style="opacity:.7">No posts</li>
                        @endforelse
                    </ul>
                    @break

                @case('archives')
                    @php
                        $driver = config('database.connections.'.config('database.default').'.driver');
                        $archives = collect();
                        if ($driver === 'pgsql') {
                            $archives = \App\Models\Content::query()
                                ->ofType('post')
                                ->published()
                                ->selectRaw("to_char(published_at, 'YYYY-MM') as ym, count(*) as total")
                                ->whereNotNull('published_at')
                                ->groupBy('ym')
                                ->orderByDesc('ym')
                                ->limit(12)
                                ->get();
                        }
                    @endphp
                    <ul style="list-style:none;padding:0;margin:0">
                        @forelse ($archives as $row)
                            <li style="margin-bottom:.35rem">{{ $row->ym }} ({{ $row->total }})</li>
                        @empty
                            <li style="opacity:.7">No archives</li>
                        @endforelse
                    </ul>
                    @break

                @case('menu')
                    @php
                        $menuId = (int) ($widget->settings['menu_id'] ?? 0);
                        $menu = $menuId
                            ? \App\Models\Menu::query()->with('items')->find($menuId)
                            : \App\Models\Menu::query()->with('items')->first();
                    @endphp
                    <ul style="list-style:none;padding:0;margin:0">
                        @foreach (($menu?->items ?? collect()) as $item)
                            <li style="margin-bottom:.35rem"><a href="{{ $item->url }}">{{ $item->title }}</a></li>
                        @endforeach
                    </ul>
                    @break

                @case('text')
                    <div style="white-space:pre-wrap">{!! nl2br(e($widget->settings['content'] ?? '')) !!}</div>
                    @break

                @case('custom_html')
                    <div>{!! $widget->settings['content'] ?? '' !!}</div>
                    @break
            @endswitch
        </section>
    @empty
        <x-search-form />
        <h3 style="margin:1.5rem 0 .75rem;font-size:1rem">Categories</h3>
        <ul style="list-style:none;padding:0;margin:0">
            @foreach (\App\Models\Category::query()->orderBy('name')->limit(12)->get() as $category)
                <li style="margin-bottom:.35rem"><a href="{{ url('/category/'.$category->slug) }}">{{ $category->name }}</a></li>
            @endforeach
        </ul>
    @endforelse
</aside>
