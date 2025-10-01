@extends('layouts.staff')

@section('title', '勤怠詳細')

@section('content')
<x-page-title>勤怠詳細</x-page-title>

@if (session('status'))
<div class="alert alert-success">{{ session('status') }}</div>
@endif

@if ($errors->any())
<div class="alert alert-danger">
  <div>入力エラーがあります</div>
  <ul style="margin-left:1em;">
    @foreach ($errors->all() as $msg)
    <li>{{ $msg }}</li>
    @endforeach
  </ul>
</div>
@endif

  <div class="card" style="max-width:760px;margin:auto;">
    <div class="card__body">
      <div class="grid grid-cols-2 gap-3">
      </div>
      <label class="label">名前</label>
      <div class="read">{{ Auth::user()->name }}</div>
    </div>
    <div>
      <label class="label">日付</label>
      <div class="read">{{ \Carbon\Carbon::parse($attendance->work_date)->isoFormat('YYYY/MM/DD(dd)') }}</div>
    </div>
  </div>
  <form method="POST" action="{{ route('requests.store', ['date' => $attendance->work_date]) }}">
    @csrf

    {{-- 出勤・退勤 --}}
      <div class="grid grid-cols-2 gap-3" style="margin-top:16px;">
        <div>
          <label class="label">出勤</label>
          <input class="input @error('clock_in') is-invalid @enderror"
                  type="time" name="clock_in"
                  value="{{ old('clock_in', optional($attendance->clock_in)->format('H:i')) }}">
          @error('clock_in')<p class="error">{{ $message }}</p>@enderror
        </div>
        <div>
          <label class="label">退勤</label>
          <input class="input @error('clock_out') is-invalid @enderror"
                  type="time" name="clock_out"
                  value="{{ old('clock_out', optional($attendance->clock_out)->format('H:i')) }}">
          @error('clock_out')<p class="error">{{ $message }}</p>@enderror
        </div>
      </div>

      {{-- 休憩 --}}
      <div style="margin-top:16px;">
        <label class="label">休憩</label>

        @php
        $oldBreaks = collect(old('breaks', $breaks));
        if($oldBreaks->isEmpty() || end($oldBreaks)['start'] ?? '' || end($oldBreaks)['end'] ?? '') {
          $oldBreaks = $oldBreaks->push(['start' => '', 'end'=> '']);
        }
        @endphp

        @foreach($oldBreaks as $i => $b)
        <div class="grid grid-cols-2 gap-3" style="margin-bottom:8px;">
          <div>
            <input class="input @error("breaks.$i.start") is-invalid @enderror"
                      type="time" name="breaks[{{ $i }}][start]"
                      value="{{ $b['start'] ?? '' }}" placeholder="開始">
              @error("breaks.$i.start")<p class="error">{{ $message }}</p>@enderror
          </div>
            <input class="input @error("breaks.$i.end") is-invalid @enderror"
                      type="time" name="breaks[{{ $i }}][end]"
                      value="{{ $b['end'] ?? '' }}" placeholder="終了">
              @error("breaks.$i.end")<p class="error">{{ $message }}</p>@enderror
            </div>
        </div>
        @endforeach
      </div>
        {{-- 備考（必須・最大500） --}}
      <div style="margin-top:16px;">
        <label class="label">備考（修正理由）</label>
        <textarea class="textarea @error('note') is-invalid @enderror"
                  name="note" rows="4" placeholder="修正理由を記入してください">{{ old('note') }}</textarea>
        @error('note')<p class="error">{{ $message }}</p>@enderror
      </div>

      <div style="margin-top:20px;">
        <x-button type="submit" variant="primary">修正申請する</x-button>
      </div>
    </form>
  </div>
</div>
@endsection