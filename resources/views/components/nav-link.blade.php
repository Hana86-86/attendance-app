@props(['href' => '#', 'active' => false])

<a {{ $attributes->marge([
    'href => $href',
    'class' => $active ? 'active' : ''
    ]) }}>
    {{ $slot }} {{-- リンクの文字 --}}
</a>

