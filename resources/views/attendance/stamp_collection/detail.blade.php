@extends('layouts.staff')

@section('title','申請詳細')
@section('content')
<div class="card" style="max-width:760px;margin:auto;">
  @php $p = $req->payload ?? []; @endphp

  <x-page-title>申請詳細</x-page-title>

  <div class="field">
    <label>対象日</label>
    <input class="input" type="text"
           value="{{ ($p['date'] ?? '') ? \Carbon\Carbon::parse($p['date'])->format('Y/m/d') : '' }}"
           readonly>
  </div>

  <div class="grid" style="gap:12px;grid-template-columns:1fr 1fr;margin-top:12px;">
    <div class="field">
      <label>出勤</label>
      <input class="input" type="text" value="{{ $p['clock_in'] ?? '' }}" readonly>
    </div>
    <div class="field">
      <label>退勤</label>
      <input class="input" type="text" value="{{ $p['clock_out'] ?? '' }}" readonly>
    </div>
  </div>

  <div class="field" style="margin-top:12px;">
    <label>休憩（{{ is_countable($p['breaks'] ?? null) ? count($p['breaks']) : 0 }}件）</label>
    <div class="stack">
      @foreach(($p['breaks'] ?? []) as $i => $b)
        <div class="grid" style="gap:8px;grid-template-columns:1fr 1fr;">
          <input class="input" type="text" value="{{ $b['start'] ?? '' }}" readonly>
          <input class="input" type="text" value="{{ $b['end'] ?? '' }}" readonly>
        </div>
      @endforeach
      {{-- 追加1枠（仕様要件どおり、入力フィールド形状だが readonly） --}}
      <div class="grid" style="gap:8px;grid-template-columns:1fr 1fr;">
        <input class="input" type="text" value="" placeholder="--:--" readonly>
        <input class="input" type="text" value="" placeholder="--:--" readonly>
      </div>
    </div>
  </div>

  <div class="field" style="margin-top:12px;">
    <label>備考</label>
    <textarea class="input" rows="3" readonly>{{ $p['note'] ?? '' }}</textarea>
  </div>

  @if($isReadOnly)
    <p class="hint" style="color:#d33;margin-top:12px;">
      承認待ちのため修正はできません。
    </p>
  @endif
</div>
@endsection