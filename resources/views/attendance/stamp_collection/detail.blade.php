@extends('layouts.staff')

@section('title', '申請詳細')

@section('content')
<x-page-title>申請詳細</x-page-title>

<h1>勤怠詳細</h1>

<p>日付：{{ $dateY ?? \Carbon\Carbon::parse($date ?? now())->isoFormat('YYYY年') }}
    {{ $dateMD ?? \Carbon\Carbon::parse($date ?? now())->isoFormat('M月D日') }}
</p>

{{-- 日本語：勤怠が未登録ならメッセージだけ表示して終了 --}}
@unless($attendance)
  <p>この日はまだ打刻がありません。</p>
@else
  <ul>
    <li>出勤：{{ $attendance->clock_in?->format('H:i') ?? '—' }}</li>
    <li>退勤：{{ $attendance->clock_out?->format('H:i') ?? '—' }}</li>
    <li>状態：{{ $attendance->status }}</li>
  </ul>
@endunless
@endsection