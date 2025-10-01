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
          <th style="width:90px;">詳細</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($list as $r)
          <tr>
            <td>
              {{ \Carbon\Carbon::parse($r['date'])->isoFormat('YYYY/MM/DD') }}
              <span style="color:#666;">（{{ $r['dow'] }}）</span>
            </td>
            <td class="mono">{{ $r['clock_in'] ?: '' }}</td>
            <td class="mono">{{ $r['clock_out'] ?: '' }}</td>
            <td class="mono">{{ m2hm($r['break_min']) ?: '' }}</td>
            <td class="mono">{{ m2hm($r['work_min']) ?: '' }}</td>
            <td>
              @if (!empty($row['detail_url']) && $row['detail_url'] !== '#')
                <a class="btn btn-link" href="{{ $row['detail_url'] }}">詳細</a>
              @else
                <span class="btn btn-disabled" aria-disabled="true">詳細</span>
              @endif
            </td>
          </tr>
          @empty
          <tr><td colspan="6" style="text-align:center;color:#666;">勤怠データがありません</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection