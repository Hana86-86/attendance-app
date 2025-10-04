@extends((($role ?? 'staff') === 'admin') ? 'layouts.admin' : 'layouts.staff') 

@section('title', '勤怠詳細')

@section('content')
@php
  $role     = $role     ?? 'staff';                 // 'staff' | 'admin'
  $status   = $status   ?? 'editable';              // 'editable' | 'pending' | 'approved'
  $canEdit  = (bool)($canEdit ?? false);            // 入力可否
  $footer   = $footer   ?? 'request';               // 'request'|'message'|'admin_update'|'approve'|'approved'
  $form     = $form     ?? null;                    // ['action'=>..., 'method'=>...]
  $timeMode = $canEdit ? 'input' : 'text';
  // $form が null でも安全に取り出す
  $formAction = is_array($form) ? ($form['action'] ?? '') : '';
  $formMethod = is_array($form) ? ($form['method'] ?? 'post') : 'post';
@endphp

<x-page-title>勤怠詳細</x-page-title>

@if($formAction !== '')
  <form method="post" action="{{ $formAction }}">
    @csrf
    @if(strtolower($formMethod) !== 'post')
      @method($formMethod)
    @endif
@endif

<div class="card" style="max-width:720px;">
  <table width="100%" cellspacing="0" cellpadding="10" style="border-collapse:separate; border-spacing:0;">
    <colgroup>
      <col style="width:140px;">
      <col style="width:220px;">
      <col style="width:40px;">
      <col style="width:220px;">
    </colgroup>
    <thead>
      <tr>
        <th></th><th></th><th></th><th></th>
      </tr>
    </thead>

    <tbody>
      {{-- 名前 --}}
      <tr style="border-bottom:1px solid #f7f7f7;">
        <td style="color:#666;">名前</td>
        <td colspan="3">
          @if($canEdit && $role==='admin')
            <input type="text" value="{{ $name }}"
              style="width:100%; height:38px; border:1px solid #ddd; border-radius:6px; padding:0 10px;">
          @else
            <div style="height:38px; border:1px solid #ddd; border-radius:6px; padding:0 10px; display:flex; align-items:center;">
              {{ $name }}
            </div>
          @endif
        </td>
      </tr>

      {{-- 日付 --}}
      <tr style="border-bottom:1px solid #f7f7f7;">
        <td style="color:#666;">日付</td>
        <td>
          <div class="mono" style="height:38px; border:1px solid #ddd; border-radius:6px; padding:0 10px; display:flex; align-items:center;">
            {{ $dateY }}
          </div>
        </td>
        <td></td>
        <td>
          <div class="mono" style="height:38px; border:1px solid #ddd; border-radius:6px; padding:0 10px; display:flex; align-items:center;">
            {{ $dateMD }}
          </div>
        </td>
      </tr>

      {{-- 出勤・退勤 --}}
      <x-time-row
      label="出勤・退勤"
      nameStart="clock_in"
      nameEnd="clock_out"
      :start="$clockIn ?? ''"
      :end="$clockOut ?? ''"
      :mode="$timeMode"
      />

      {{-- 休憩 1 --}}
      <x-time-row
      label="休憩"
      nameStart="breaks[0][start]"
      nameEnd="breaks[0][end]"
      :start="$break1In ?? ''"
      :end="$break1Out ?? ''"
      :mode="$timeMode"
      />

      {{-- 休憩２（空欄OK）--}}
      <x-time-row
      label="休憩2"
      nameStart="breaks[1][start]"
      nameEnd="breaks[1][end]"
      :start="$break2In ?? ''"
      :end="$break2Out ?? ''"
      :mode="$timeMode"
      />

      {{-- 備考（必須） --}}
      <tr>
      <td style="color:#666;">備考</td>
        <td colspan="3">
        <textarea
        name="note"
        rows="3"
        required
        maxlength="500"
        placeholder="備考を入力"
        style="width:100%; height:90px; border:1px solid #ddd; border-radius:6px; padding:8px 10px;">
        {{ old('note', $note ?? '') }}</textarea>
        @error('note')
            <p class="error" style="color:#e06;margin-top:6px;">{{ $message }}</p>
        @enderror
        </td>
      </tr>
    </tbody>
  </table>

  {{-- 承認待ちメモ（スタッフ・pending のとき） --}}
  @if($role==='staff' && $status==='pending')
    <div style="margin-top:10px; color:#e06; font-size:12px;">
      ※ 承認待ちのため修正はできません。
    </div>
  @endif

  {{-- 右下フッター（ボタンだけ） --}}
  <div style="text-align:right; margin-top:12px;">
    @switch($footer)
      @case('request')       {{-- スタッフ：修正申請 --}}
      @case('admin_update')  {{-- 管理者：直接修正 --}}
        <x-button type="submit" variant="primary">修正</x-button>
      @break

      @case('approve')       {{-- 管理者：申請を承認 --}}
        <x-button type="submit" variant="primary">承認</x-button>
      @break

      @case('approved')      {{-- 管理者：承認済み表示 --}}
        <span class="btn btn-disabled" aria-disabled="true">承認済み</span>
      @break

      @case('message')       {{-- スタッフ：承認待ちで編集不可 --}}
        <p style="color:#e06;margin-bottom:6px;">※ 承認待ちのため修正はできません。</p>
        <span class="btn btn-disabled" aria-disabled="true">承認待ち</span>
      @break
    @endswitch
  </div>
</div>

@if($formAction !== '')
  </form>
@endif
@endsection