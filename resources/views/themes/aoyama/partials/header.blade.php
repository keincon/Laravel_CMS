@php
    $menu = \App\Models\Menu::query()
        ->where(function ($q) {
            $q->where('slug', 'primary')->orWhere('location', 'primary');
        })
        ->with([
            'items' => fn ($q) => $q->whereNull('parent_id')->orderBy('sort_order')->with(['children', 'page']),
        ])
        ->first();

    $itemUrl = static function ($item): string {
        if (! empty($item->url)) {
            return $item->url;
        }
        if ($item->page) {
            return url('/'.$item->page->slug);
        }

        return '#';
    };
@endphp

<header class="ao-header">
    <div class="ao-util">
        <div class="ao-container ao-util-inner">
            <a class="is-alert" href="{{ url('/support-userguide-funshitsu-php') }}">カード紛失・盗難</a>
            <a href="{{ url('/news') }}">お知らせ</a>
            <a href="{{ url('/company-about') }}">企業情報</a>
            <a href="{{ url('/faq') }}">FAQ</a>
        </div>
    </div>

    <div class="ao-container ao-header-main">
        <a class="ao-logo" href="{{ url('/') }}" aria-label="青山キャピタル">
            @include('themes.aoyama.partials.image-helper')
            <img
                src="{{ aoyama_theme_image('logo.png') }}"
                alt="青山キャピタル"
                width="160"
                height="36"
                onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';"
            >
            <span class="ao-logo-fallback" style="display:none">青山キャピタル</span>
        </a>

        <div class="ao-nav-wrap">
            <button
                type="button"
                class="ao-menu-toggle"
                data-ao-menu-toggle
                aria-expanded="false"
                aria-controls="ao-primary-nav"
                aria-label="メニューを開く"
            >
                <span></span>
            </button>

            <ul class="ao-nav" id="ao-primary-nav" role="list">
                @if ($menu && $menu->items->isNotEmpty())
                    @foreach ($menu->items as $item)
                        <li>
                            <a href="{{ $itemUrl($item) }}">
                                {{ $item->title }}
                                @if ($item->children->isNotEmpty())
                                    <span class="ao-caret" aria-hidden="true">▼</span>
                                @endif
                            </a>
                            @if ($item->children->isNotEmpty())
                                <ul class="ao-dropdown" role="list">
                                    @foreach ($item->children as $child)
                                        <li>
                                            <a href="{{ $itemUrl($child) }}">{{ $child->title }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                @else
                    <li><a href="{{ url('/card') }}">カードをつくる</a></li>
                    <li><a href="{{ url('/service') }}">サービス・特典</a></li>
                    <li><a href="{{ url('/cashless') }}">キャッシング</a></li>
                    <li><a href="{{ url('/campaign') }}">キャンペーン</a></li>
                    <li><a href="{{ url('/membership') }}">カード会員の方</a></li>
                @endif
            </ul>
        </div>
    </div>
</header>

<div class="ao-drawer-backdrop" data-ao-drawer-backdrop></div>
