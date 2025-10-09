@extends('layouts.staff')

@section('title','勤怠')
@section('main_class','--centered')

@section('content')
<div class="card --centered">
  {{-- ステータス・バッジ（グレー固定） --}}
  <div class="state-badge">
    <span class="{{ $badge['class'] }}">{{ $badge['text'] }}</span>
  </div>

  {{-- 日付・時刻表示（Figmaの見た目：年-月日(曜日) と 大きい時刻） --}}
  <div class="date-meta">
    <div class="y">{{ $dateY }}</div>        {{-- 例: 2025年 --}}
    <div class="md">{{ $dateMD }}</div>      {{-- 例: 10月4日 (土) --}}
  </div>
  <div class="big-time">{{ now()->format('H:i') }}</div>

  @switch($state)
    @case('not_working')
        {{-- 出勤前：出勤ボタンのみ --}}
        <div class="actions">
            <form method="POST" action="{{ route('attendance.clock-in') }}">
                @csrf
                <button type="submit" class="btn-primary">出勤</button>
            </form>
        </div>
        @break

    @case('working')
        {{-- 出勤後：退勤・休憩入 --}}
        <div class="actions">
            <form method="POST" action="{{ route('attendance.clock-out') }}">
                @csrf
                <button type="submit" class="btn-primary">退勤</button>
            </form>
            <form method="POST" action="{{ route('attendance.break-in') }}">
                @csrf
                <button type="submit" class="btn-secondary">休憩入</button>
            </form>
        </div>
        @break

    @case('on_break')
        {{-- 休憩中：休憩戻 --}}
        <div class="actions">
            <form method="POST" action="{{ route('attendance.break-out') }}">
                @csrf
                <button type="submit" class="btn-secondary">休憩戻</button>
            </form>
        </div>
        @break

    @case('closed')
        {{-- 退勤後：お疲れ様でした --}}
        <div class="finished">お疲れ様でした。</div>
        @break
@endswitch
</div>
@endsection