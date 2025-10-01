@props(['title' => null])

<h2 class="page-title">{{ $title ?? $slot }}</h2>