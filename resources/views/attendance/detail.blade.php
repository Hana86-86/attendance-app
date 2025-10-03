@extends(($role ?? 'staff') === 'admin' ? 'layouts.admin' : 'layouts.staff')

@section('title', '勤怠詳細')

@section('content')
@php
  $role     = $role     ?? 'staff';                 // 'staff' | 'admin'
  $status   = $status   ?? 'editable';              // 'editable' | 'pending' | 'approved'
  $canEdit  = (bool)($canEdit ?? false);            // 入力可否
  $footer   = $footer   ?? 'request';               // 'request'|'message'|'admin_update'|'approve'|'approved'
  $form     = $form     ?? null;                    // ['action'=>..., 'method'=>...]
  $timeMode = $canEdit ? 'input' : 'text';
@endphp

<x-page-title>勤怠詳細</x-page-title>

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
            {{ \Carbon\Carbon::parse($date)->isoFormat('YYYY年') }}
          </div>
        </td>
        <td></td>
        <td>
          <div class="mono" style="height:38px; border:1px solid #ddd; border-radius:6px; padding:0 10px; display:flex; align-items:center;">
            {{ \Carbon\Carbon::parse($date)->isoFormat('M月D日') }}
          </div>
        </td>
      </tr>

      {{-- 出勤・退勤 --}}
      <x-time-row label="出勤・退勤" :start="$clockIn ?? ''" :end="$clockOut ?? ''" :mode="$timeMode" />

      {{-- 休憩 --}}
      <x-time-row label="休憩"    :start="$break1In ?? ''" :end="$break1Out ?? ''" :mode="$timeMode" />

      {{-- 休憩2（空欄OK） --}}
      <x-time-row label="休憩2"   :start="$break2In ?? ''" :end="$break2Out ?? ''" :mode="$timeMode" />

      {{-- 備考 --}}
      <tr>
        <td style="color:#666;">備考</td>
        <td colspan="3">
        @if($canEdit)
          <textarea name="note" rows="3" placeholder="備考を入力"
            style="width:100%; border:1px solid #ddd; border-radius:6px; padding:8px 10px;">{{ old('note', $note) }}</textarea>
        @else
          <div style="min-height:38px; border:1px solid #ddd; border-radius:6px; padding:8px 10px;">
            {{ $note !== '' ? $note : '—' }}
        </div>
    @endif
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

  {{-- 右下フッター（役割・状態で切替） --}}
  <div style="text-align:right; margin-top:12px;">
    @switch($footer)
      @case('request')      {{-- スタッフ：修正申請 --}}
        <form method="post" action="{{ $form['action'] }}">
          @csrf
          <x-button variant="primary">修正</x-button>
        </form>
      @break

      @case('message')      {{-- スタッフ：申請済みで編集不可 --}}
        <span class="btn btn-disabled" aria-disabled="true">承認待ち</span>
      @break

      @case('admin_update') {{-- 管理者：直接修正 --}}
        <form method="post" action="{{ $form['action'] }}">
          @csrf
          <x-button variant="primary">修正</x-button>
        </form>
      @break

      @case('approve')      {{-- 管理者：申請詳細 承認ボタン --}}
        <form method="post" action="{{ $form['action'] }}">
          @csrf
          <x-button variant="primary">承認</x-button>
        </form>
      @break

      @case('approved')     {{-- 管理者：申請詳細 承認済み表示 --}}
        <span class="btn btn-disabled" aria-disabled="true">承認済み</span>
      @break
    @endswitch
  </div>
</div>
@endsection