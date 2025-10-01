{{-- 受け取る値（props）を宣言 --}}
@props([
    'label',
    'start' => '',
    'end'   => '',
    'mode'  => 'input', // 'input' or 'text'（表示モード）
])

@php
  // input共通スタイル（列幅いっぱいに広げる）
  $inputStyle = 'width:100%; height:38px; box-sizing:border-box; border:1px solid #ddd; border-radius:6px; padding:0 10px;';
  $boxStyle   = 'height:38px; box-sizing:border-box; border:1px solid #ddd; border-radius:6px; padding:0 10px; display:flex; align-items:center;';
@endphp

<tr style="border-bottom:1px solid #f7f7f7;">
  {{-- 左ラベル列 --}}
  <td style="color:#666;">{{ $label }}</td>

  {{-- 開始 --}}
  <td>
    @if($mode === 'input')
      <input type="time" value="{{ $start }}" style="{{ $inputStyle }}">
    @else
      <div style="{{ $boxStyle }}">{{ $start !== '' ? $start : '—' }}</div>
    @endif
  </td>

  {{-- 真ん中の「〜」 --}}
  <td style="text-align:center; color:#999;">〜</td>

  {{-- 終了 --}}
  <td>
    @if($mode === 'input')
      <input type="time" value="{{ $end }}" style="{{ $inputStyle }}">
    @else
      <div style="{{ $boxStyle }}">{{ $end !== '' ? $end : '—' }}</div>
    @endif
  </td>
</tr>