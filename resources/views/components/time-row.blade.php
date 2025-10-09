@props([
  'label' => '',
  'start' => '',
  'end'   => '',
  'nameStart' => '',
  'nameEnd'   => '',
  'mode'  => 'input',  // 'input' or 'text'
])

@php
  $inputStyle = 'width:100%; height:38px; box-sizing:border-box; border:1px solid #ddd; border-radius:6px; padding:0 10px;';
  $boxStyle   = 'height:38px; box-sizing:border-box; border:1px solid #ddd; border-radius:6px; padding:0 10px; display:flex; align-items:center;';

  // name 属性 -> エラー/old 用のキーに変換（breaks[0][start] => breaks.0.start）
  $toDot = fn(string $s) => str_replace(['[',']'], ['.', ''], $s);
  $errStart = $toDot($nameStart);
  $errEnd   = $toDot($nameEnd);

  // old の値を優先（未入力なら既存の $start/$end）
  $valStart = old($errStart, $start);
  $valEnd   = old($errEnd, $end);
@endphp

<tr style="border-bottom:1px solid #f7f7f7;">
  <td style="color:#666;">{{ $label }}</td>

  {{-- 開始 --}}
  <td>
    @if ($mode === 'input')
      <input type="time" name="{{ $nameStart }}" value="{{ $valStart }}" style="{{ $inputStyle }}">
      @error($errStart)
        <p class="error" style="color:#e06;margin-top:6px;">{{ $message }}</p>
      @enderror
    @else
      <div style="{{ $boxStyle }}">{{ $start !== '' ? $start : 'ー' }}</div>
    @endif
  </td>

  {{-- 〜 --}}
  <td style="text-align:center; color:#999;">〜</td>

  {{-- 終了 --}}
  <td>
    @if ($mode === 'input')
      <input type="time" name="{{ $nameEnd }}" value="{{ $valEnd }}" style="{{ $inputStyle }}">
      @error($errEnd)
        <p class="error" style="color:#e06;margin-top:6px;">{{ $message }}</p>
      @enderror
    @else
      <div style="{{ $boxStyle }}">{{ $end !== '' ? $end : 'ー' }}</div>
    @endif
  </td>
</tr>