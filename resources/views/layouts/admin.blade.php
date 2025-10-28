@extends('layouts.base')

@section('nav')
<nav class="nav-bar">
  <ul class="nav-links">
    {{-- 勤怠一覧 --}}
<li>
  <a href="{{ route('admin.attendances.today') }}"
      class="{{ request()->routeIs('admin.attendances.*') ? 'active' : '' }}">
      勤怠一覧
    </a>
</li>

{{-- スタッフ一覧 --}}
<li>
  <a href="{{ route('admin.users.index') }}"
      class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
      スタッフ一覧
    </a>
</li>

{{-- ページ内タブで 承認待ち/承認済み を切替 --}}
    <li>
      <a href="{{ route('admin.requests.index', ['status' => 'pending']) }}"
          class="{{ request()->routeIs('admin.requests.*') ? 'active' : '' }}">
        申請一覧
      </a>
    </li>

    {{-- ログアウト --}}
    <li class="nav-logout">
      <form method="POST" action="{{ route('admin.logout') }}">
        @csrf
        <button type="submit" class="as-link">ログアウト</button>
      </form>
    </li>
  </ul>
</nav>
@endsection