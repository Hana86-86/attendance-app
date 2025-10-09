@extends('layouts.base')

@section('nav'){{-- ゲストはナビ非表示 --}}@endsection

@section('content')
<div class="card" style="max-width:560px; margin:24px auto;">
    @yield('guest_content')
</div>
@endsection