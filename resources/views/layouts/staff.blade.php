@extends('layouts.base')

@section('title','スタッフ | 勤怠')

{{-- スタッフナビ --}}

@section('nav')
@php
  // 現在月が渡っていない場合は今月を使う
  $currentMonth = $currentMonth ?? now()->format('Y-m');
@endphp

<nav class="nav">
  <a href="{{ route('attendance.create') }}"
     class="{{ request()->routeIs('attendance.create') ? 'active' : '' }}">勤怠</a>

  <a href="{{ route('attendance.list', ['month' => now()->format('Y-m')]) }}"
   class="{{ request()->routeIs('attendance.list') || request()->routeIs('attendance.detail') ? 'active' : '' }}">
  勤怠一覧
</a>

  <a href="{{ route('requests.list') }}"
     class="{{ request()->routeIs('requests.*') ? 'active' : '' }}">申請</a>

  <form method="POST" action="{{ route('logout') }}" class="nav_logout">
    @csrf
    <button type="submit">ログアウト</button>
  </form>
</nav>
@endsection