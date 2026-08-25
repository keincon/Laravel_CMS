@props(['items' => []])

@if (! empty($items))
<nav aria-label="Breadcrumb">
    <ol class="breadcrumb">
        @foreach ($items as $item)
            <li>
                @if (! empty($item['url']))
                    <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                @else
                    <span aria-current="page">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
@endif
