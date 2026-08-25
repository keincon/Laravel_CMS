@props(['action' => null, 'query' => '', 'placeholder' => 'Search…'])

<form class="site-search" action="{{ $action ?: url('/search') }}" method="GET" role="search">
    <input type="search" name="q" value="{{ $query }}" placeholder="{{ $placeholder }}" aria-label="Search">
    <button class="cta-btn" type="submit" style="margin-left:.35rem;padding:.4rem .8rem;font-size:.9rem">Search</button>
</form>
