@extends('layouts.staff')

@section('title','勤怠')

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

  {{-- フラッシュメッセージ --}}
  @if (session('status'))
    <div class="alert">{{ session('status') }}</div>
  @endif

  {{-- バリデーションエラー（1行ずつ） --}}
  @if ($errors->any())
    <div class="error">
      @foreach ($errors->all() as $msg)
        <div>{{ $msg }}</div>
      @endforeach
    </div>
  @endif

  {{-- ボタン出し分け：Figmaどおりの4状態 --}}
  @switch($state)

    {{-- ① 未出勤：出勤のみ（黒） --}}
    @case('not_working')
      <form method="POST" action="{{ route('attendance.clock-in') }}" class="actions --single">
        @csrf
        <button type="submit" class="btn btn-primary">出勤</button>
      </form>
      @break

    {{-- ② 勤務中：退勤（黒）＋ 休憩入（グレー） --}}
    @case('working')
      <div class="actions --double">
        <form method="POST" action="{{ route('attendance.clock-out') }}">
          @csrf
          <button type="submit" class="btn btn-primary">退勤</button>
        </form>
        <form method="POST" action="{{ route('attendance.break-in') }}">
          @csrf
          <button type="submit" class="btn btn-secondary">休憩入</button>
        </form>
      </div>
      @break

    {{-- ③ 休憩中：休憩戻（黒）のみ --}}
    @case('on_break')
      <form method="POST" action="{{ route('attendance.break-out') }}" class="actions --single">
        @csrf
        <button type="submit" class="btn btn-primary">休憩戻</button>
      </form>
      @break

    {{-- ④ 退勤済：メッセージのみ --}}
    @case('closed')
      <div class="finished">お疲れ様でした。</div>
      @break
  @endswitch
</div>
@endsection