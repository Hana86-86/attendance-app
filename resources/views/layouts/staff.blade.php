@extends('layouts.base')

@section('title', 'スタッフ | 勤怠')

{{-- ヘッダーのナビだけ定義 --}}
@section('nav')
@php
  $currentMonth = $currentMonth ?? now()->format('Y-m');
@endphp

<nav class="nav-bar">
  <ul class="nav-links">
    <li>
      <a href="{{ route('attendance.create', ['date' => today()->toDateString()]) }}"
          class="{{ request()->routeIs('attendance.create') ? 'active' : '' }}">
        勤怠
      </a>
    </li>
    <li>
      <a href="{{ route('attendance.list', ['month' => $currentMonth]) }}"
          class="{{ request()->routeIs('attendance.list') || request()->routeIs('attendance.detail') ? 'active' : '' }}">
        勤怠一覧
      </a>
    </li>
    <li>
      <a href="{{ route('requests.list', ['status' => request('status','pending')]) }}"
          class="{{ request()->routeIs('requests.*') ? 'active' : '' }}">
        申請一覧
      </a>
    </li>
  </ul>

  <form method="POST" action="{{ route('logout') }}" class="nav-logout">
    @csrf
    <button type="submit" class="as-link">ログアウト</button>
  </form>
</nav>
@endsection