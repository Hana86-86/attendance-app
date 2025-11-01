@props([
  // ラベル表示
  'label' => '',
  // name属性（開始・終了） 例: "clock_in" / "breaks[0][start]"
  'nameStart' => '',
  'nameEnd'   => '',
  // 値（時刻文字列 "HH:MM" または 空文字）
  'start' => '',
  'end'   => '',
  // 'input' or 'text'（入力欄か表示専用か）
  'mode'  => 'input',
])

@php
  // ─────────────────────────────────────────────
  // 1) エラーキーをブラケット記法 → ドット記法に変換
  //    ex) breaks[0][start] -> breaks.0.start
  // ─────────────────────────────────────────────
  $toDot = function (string $name) {
    // breaks[0][start] → breaks.0.start
    $s = preg_replace('/\]/', '', $name);        // 'breaks[0][start' へ
    $s = preg_replace('/\[/','.',$s);            // 'breaks.0.start'
    return $s;
  };

  $errStart = $toDot($nameStart);
  $errEnd   = $toDot($nameEnd);

  // ─────────────────────────────────────────────
  // 2) 値の表示用（テキストモード表示）
  // ─────────────────────────────────────────────
  $ghostStyle = "height:38px; box-sizing:border-box; border:1px solid #ddd; border-radius:6px; padding:0 10px; display:flex; align-items:center;";

  // 入力欄の共通スタイル
  $inputStyle = "width:100%; height:38px; box-sizing:border-box; border:1px solid #ddd; border-radius:6px; padding:0 10px;";

  // 旧UI（テキスト表示時）の「〜」の間に入れる表示
  $valStart = $start;
  $valEnd   = $end;
@endphp

<tr style="border-bottom:1px solid #f7f7f7;">
  <td style="color:#666;">{{ $label }}</td>

  {{-- ← 開始 --}}
  <td>
    @if ($mode === 'input')
      <input type="time" name="{{ $nameStart }}" value="{{ $valStart }}" style="{{ $inputStyle }}">
      @error($errStart)
        <p class="error" style="color:#e06;margin-top:6px;">{{ $message }}</p>
      @enderror
    @else
      <div style="{{ $ghostStyle }}">{{ $valStart !== '' ? $valStart : '—' }}</div>
    @endif
  </td>

  {{-- 〜（区切り） --}}
  <td style="text-align:center; color:#999;">〜</td>

  {{-- → 終了 --}}
  <td>
    @if ($mode === 'input')
      <input type="time" name="{{ $nameEnd }}" value="{{ $valEnd }}" style="{{ $inputStyle }}">
      @error($errEnd)
        <p class="error" style="color:#e06;margin-top:6px;">{{ $message }}</p>
      @enderror
    @else
      <div style="{{ $ghostStyle }}">{{ $valEnd !== '' ? $valEnd : '—' }}</div>
    @endif
  </td>
</tr>