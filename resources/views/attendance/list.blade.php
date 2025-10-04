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
    @forelse ($list as $row)
      @php
        $displayDate = \Carbon\Carbon::parse($row['work_date'])->isoFormat('YYYY/MM/DD');
        $detailDate  = \Carbon\Carbon::parse($row['work_date'])->format('Y-m-d');
      @endphp
      <tr>
        <td>{{ $displayDate }}</td>
        <td class="mono">{{ $row['clock_in']  ?? '' }}</td>
        <td class="mono">{{ $row['clock_out'] ?? '' }}</td>
        <td class="mono">{{ m2hm($row['break_min'] ?? 0) }}</td>
        <td class="mono">{{ m2hm($row['work_min']  ?? 0) }}</td>
        <td class="mono">
          {{-- クリック可能は a タグのみ。無効日にしたい時は a ではなく span を出す --}}
          <a class="btn-link" href="{{ route('attendance.detail', ['date' => $detailDate]) }}">詳細</a>
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="6" style="text-align:center; color:#666;">勤怠データがありません。</td>
      </tr>
    @endforelse
    </tbody>
  </table>
</div>
@endsection