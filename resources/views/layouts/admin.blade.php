@extends('layouts.base')

@section('nav')
<nav class="nav">
{{-- 日次一覧：パラメータ不要の today ルートに変更 --}}
<a href="{{ route('admin.attendances.today') }}"
   class="{{ request()->routeIs('admin.attendances.*') ? 'active' : '' }}">
   勤怠一覧
</a>

<a href="{{ route('admin.users.index') }}"
   class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
   スタッフ一覧
</a>

<a href="{{ route('admin.requests.pending') }}"
   class="{{ request()->routeIs('admin.requests.*') ? 'active' : '' }}">
   申請一覧
</a>

<form method="POST" action="{{ route('admin.logout') }}" style="display:inline">
   @csrf
   <button type="submit">ログアウト</button>
</form>
</nav>
@endsection