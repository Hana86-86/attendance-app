{{-- 管理者: スタッフ別 勤怠一覧（Figma右の縦長一覧） --}}
@extends('layouts.admin')

@section('title', 'スタッフ別勤怠一覧')

@section('nav')
  {{-- 管理者用ナビ（レイアウトの @yield('nav') に入る） --}}
  <a href="/_mock/admin/attendances" class="{{ request()->is('_mock/admin/attendances') ? 'active' : '' }}">勤怠一覧</a>
  <a href="/_mock/admin/users" class="{{ request()->is('_mock/admin/users') ? 'active' : '' }}">スタッフ一覧</a>
  <a href="/_mock/admin/requests" class="{{ request()->is('_mock/admin/requests*') ? 'active' : '' }}">申請一覧</a>
@endsection

@section('content')
  <x-page-title>西 伶奈さんの勤怠</x-page-title>

  {{-- 月切り替えバー（見た目のみ） --}}
  <div class="card" style="max-width:980px">
    <div class="row" style="display:flex; align-items:center; gap:12px; padding:12px">
      <a href="#" class="btn">← 前月</a>
      <div class="muted" style="display:flex; align-items:center; gap:8px">
        <span>📅</span>
        <strong>2023/06</strong>
      </div>
      <a href="#" class="btn" style="margin-left:auto">翌月 →</a>
    </div>
  </div>

  {{-- 一覧テーブル（骨組み） --}}
  <div class="card" style="max-width:980px">
    <table class="table">
      <thead>
        <tr>
          <th>日付</th>
          <th>出勤</th>
          <th>退勤</th>
          <th>休憩</th>
          <th>合計</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @for ($i = 1; $i <= 20; $i++)
          <tr>
            <td>06/{{ sprintf('%02d', $i) }}(木)</td>
            <td>09:00</td>
            <td>18:00</td>
            <td>1:00</td>
            <td>8:00</td>
            <td><a href="/_mock/admin/attendances/show" class="btn">詳細</a></td>
          </tr>
        @endfor
      </tbody>
    </table>

    {{-- 右下CSVボタン（Figma準拠のダミー） --}}
    <div style="display:flex; justify-content:flex-end; padding:12px">
      <a href="#" class="btn">CSV出力</a>
    </div>
  </div>
@endsection