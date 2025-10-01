@extends('layouts.admin')

@section('content')
  <x-page-title>勤怠詳細（申請）</x-page-title>

  <div class="card" style="display:grid; gap:14px; max-width:700px;">
    <div class="field"><label>名前</label><input class="input" value="西 伶奈" readonly></div>
    <div class="field"><label>日付</label><input class="input" type="date" value="2023-06-01" readonly></div>

    {{-- 申請内容：出勤/退勤/休憩の希望値（読み取り） --}}
    <div style="display:grid; gap:10px; grid-template-columns:1fr 1fr;">
      <div class="field"><label>申請：出勤</label><input class="input" value="09:00" readonly></div>
      <div class="field"><label>申請：退勤</label><input class="input" value="18:00" readonly></div>
    </div>
    <div style="display:grid; gap:10px; grid-template-columns:1fr 1fr;">
      <div class="field"><label>申請：休憩開始</label><input class="input" value="12:00" readonly></div>
      <div class="field"><label>申請：休憩終了</label><input class="input" value="13:00" readonly></div>
    </div>

    <div class="field"><label>申請理由</label><input class="input" value="電車遅延のため" readonly></div>

    <div style="display:flex; gap:10px; margin-top:6px;">
      <x-button variant="primary">承認</x-button>
      
    </div>
  </div>
@endsection
