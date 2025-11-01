{{-- どのページにも「埋め込める」中身だけのカード。フォームで包むかはここで判定 --}}

@php
/*
| 受け取り想定のキー（Controller / Trait から渡す）
| role : 'staff' | 'admin'
| status : 'editable' | 'pending' | 'approved'
| canEdit : bool （入力可否。スタッフ承認待ち＝false）
| footer : 'request'|'message'|'admin_update'|'approve'|'approved'
| form : ['action' => url, 'method' => 'post'|'put'|'patch'|'delete'] | null
| detailId: int （管理者の承認POST用 hidden に入れる）
| name, dateY, dateMD, clockIn, clockOut, break1In, break1Out, break2In, break2Out, reason
*/

$role = $role ?? ((auth()->check() && auth()->user()->isAdmin()) ? 'admin' : 'staff');
$status = $status ?? 'editable';
$canEdit = (bool)($canEdit ?? false);
$footer = $footer ?? 'request';
$form = is_array($form ?? null) ? $form : null;

// --- フォームの有無と method/action ---
$wrapInForm = $form && !empty($form['action']);
$formAction = $form['action'] ?? route('requests.store');
$formMethod = strtolower($form['method'] ?? 'post');

// --- 管理者の承認POSTのときだけ hidden を追加（id/redirect）---
$needsApproveHidden = ($footer === 'approve') && isset($detailId);

// --- 時刻行の表示モード（入力 or 表示）---
$timeMode = $canEdit ? 'input' : 'text';

// --- バリデーションで戻ってきた時の再表示 ---
$clockIn = old('clock_in', $clockIn ?? '');
$clockOut = old('clock_out', $clockOut ?? '');
$break1In = old('breaks.0.start', $break1In ?? '');
$break1Out = old('breaks.0.end', $break1Out ?? '');
$break2In = old('breaks.1.start', $break2In ?? '');
$break2Out = old('breaks.1.end', $break2Out ?? '');
$reason = old('reason', $reason ?? '');
@endphp

@if($wrapInForm)
<form method="post" action="{{ $formAction }}" novalidate>
  @csrf
  @if($formMethod !== 'post')
  @method($formMethod)
  @endif
  @if(!empty($attendance))
  <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
  @endif
  <input type="hidden" name="date" value="{{ $date }}">
  @if($needsApproveHidden)
  <input type="hidden" name="id" value="{{ $detailId }}">
  <input type="hidden" name="redirect" value="{{ request()->fullUrl() }}">
  <input type="hidden" name="reason" value="修正申請">
  @endif
  @endif

  <div class="card">
    <table width="100%" cellspacing="0" cellpadding="10" style="border-collapse:separate; border-spacing:0;">
      <colgroup>
        <col style="width:140px;">
        <col style="width:220px;">
        <col style="width:40px;">
        <col style="width:220px;">
      </colgroup>
      <thead>
        <tr>
          <th></th>
          <th></th>
          <th></th>
          <th></th>
        </tr>
      </thead>

      <tbody>
        <tr style="border-bottom:1px solid #f7f7f7;">
          <td style="color:#666;">名前</td>
          <td colspan="3">
            @if($canEdit && $role === 'admin')
            <input type="text" value="{{ $name }}"
              style="width:100%; height:38px; border:1px solid #ddd; border-radius:6px; padding:0 10px;">
            @else
            <div style="height:38px; border:1px solid #ddd; border-radius:6px; padding:0 10px; display:flex; align-items:center;">
              {{ $name ?? '—' }}
            </div>
            @endif
          </td>
        </tr>

        {{-- 日付（常に表示のみ） --}}
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
          :start="$clockIn"
          :end="$clockOut"
          :mode="$timeMode" />

        {{-- 休憩１ --}}
        <x-time-row
          label="休憩"
          nameStart="breaks[0][start]"
          nameEnd="breaks[0][end]"
          :start="$break1In"
          :end="$break1Out"
          :mode="$timeMode" />

        {{-- 休憩２（空欄OK） --}}
        <x-time-row
          label="休憩2"
          nameStart="breaks[1][start]"
          nameEnd="breaks[1][end]"
          :start="$break2In"
          :end="$break2Out"
          :mode="$timeMode" />

        {{-- 備考：編集可なら textarea、不可なら枠付きテキスト --}}
        <tr>
          <td style="color:#666;">備考</td>
          <td colspan="3">
            @if($canEdit)
            <textarea
              name="reason"
              rows="3"
              required
              maxlength="500"
              placeholder="備考を入力"
              style="width:100%; height:90px; border:1px solid #ddd; border-radius:6px; padding:8px 10px;">{{ $reason }}</textarea>
            @error('reason')
            <p class="error" style="color:#e06;margin-top:6px;">{{ $message }}</p>
            @enderror
            @else
            <div style="min-height:38px; border:1px solid #ddd; border-radius:6px; padding:8px 10px; display:flex; align-items:center;">
              {{ $reason ?? '—' }}
            </div>
            @endif
          </td>
        </tr>
      </tbody>
    </table>

    {{-- 右下フッター（ボタン or メッセージ） --}}
    <div style="text-align:right; margin-top:12px;">
      @switch($footer)
      @case('message') {{-- スタッフ：承認待ちで修正不可（右下に赤文言） --}}
      <span style="
        display:inline-block;
        padding:8px 12px;
        color:#e06;
        font-size:14px;
        line-height:1;
      ">※ 承認待ちのため修正はできません。</span>
      @break

      @case('request') {{-- スタッフ：修正申請 --}}
      @case('admin_update') {{-- 管理者：直接修正 --}}
      <x-button type="submit" variant="primary">修正</x-button>
      @break

      @case('approve') {{-- 管理者：申請承認 --}}
      <x-button type="submit" variant="primary">承認</x-button>
      @break

      @case('approved') {{-- 管理者：承認済み（グレー背景＋白文字） --}}
      <span style="
        display:inline-block;
        padding:10px 16px;
        background:#bdbdbd;
        color:#fff;
        border-radius:8px;
        cursor:default;
      ">承認済み</span>
      @break
      @endswitch
    </div>
  </div>

  {{-- フォーム閉じ --}}
  @if($wrapInForm)
</form>
@endif