@props([
    'variant' => 'default',  //ボタンの見た目('default' or 'primary')
    'as' => 'button',  // 'a' なら<a>で出力
    'href' => '#',     // 'a' の時の遷移先
    ])
@php
    $class = $variant === 'primary' ? 'btn primary' : 'btn';
@endphp

@if ($as === 'a')
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['class' => $class, 'type' => 'submit']) }}>
        {{ $slot }}
    </button>
@endif