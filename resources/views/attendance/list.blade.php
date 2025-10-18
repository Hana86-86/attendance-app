@extends('layouts.staff')

@section('title', '勤怠一覧（' . $titleYM . '）')

@section('content')
  <x-page-title>勤怠一覧</x-page-title>

  {{-- 月ナビゲーション --}}
  <div class="card" style="display:flex;align-items:center;gap:12px;padding:12px;">
    <a class="btn" href="{{ route('attendance.list', ['month' => $prevMonth]) }}">◀ 前月</a>
    <div style="flex:1;text-align:center;font-weight:600;">{{ $titleYM }}</div>
    <a class="btn" href="{{ route('attendance.list', ['month' => $nextMonth]) }}">翌月 ▶</a>
  </div>

  {{-- 一覧テーブル --}}
  <div class="card">
  <table class="table att-table">
    <thead>
      <tr>
        <th style="width:160px;">日付</th>
        <th>出勤</th>
        <th>退勤</th>
        <th>休憩</th>
        <th>合計</th>
        <th style="width:98px;">詳細</th>
      </tr>
    </thead>

    <tbody>
@foreach ($list as $row)
  <tr>
    <td>{{ $row['work_date'] }}</td>
    <td class="mono">{{ $row['clock_in']  ?: '—' }}</td>
    <td class="mono">{{ $row['clock_out'] ?: '—' }}</td>
    <td class="mono">{{ $row['break_hm']  ?? '—' }}</td>  {{-- 休憩合計 H:MM --}}
    <td class="mono">{{ $row['work_hm']   ?? '—' }}</td>  {{-- 勤務合計 H:MM --}}
    <td class="mono">
      <a class="btn-link" href="{{ $row['detail_url'] }}">詳細</a>
    </td>
  </tr>
@endforeach

@empty($list)
  <tr><td colspan="6" class="empty">表示できるデータがありません。</td></tr>
@endempty
</tbody>

  </table>
</div>
@endsection