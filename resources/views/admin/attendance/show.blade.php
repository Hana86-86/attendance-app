@extends(($role ?? 'staff') === 'admin' ? 'layouts.admin' : 'layouts.staff')

@section('content')
@php
    $role    = $role    ?? request('role', 'staff');                 // 'staff' | 'admin'
    $status  = $status  ?? request('screen_status', 'editable');     // 'editable' | 'pending'
    $canEdit = ($role === 'admin') || ($role === 'staff' && $status !== 'pending');
    $timeMode = $canEdit ? 'input' : 'text';
@endphp

<h2 style="margin:0 0 12px 0;">勤怠詳細</h2>

<div style="background:#fff; border:1px solid #eee; border-radius:8px; padding:16px; max-width:720px;">
    <table width="100%" cellspacing="0" cellpadding="10"
            style="border-collapse:separate; border-spacing:0;">

    {{-- 列幅固定：開始と終了の幅が必ず同じになる --}}
    <colgroup>
        <col style="width:140px;"> {{-- ラベル --}}
        <col style="width:220px;"> {{-- 開始 --}}
        <col style="width:40px;">  {{-- 〜 --}}
        <col style="width:220px;"> {{-- 終了 --}}
    </colgroup>

    <thead>
    <tr>
        <th style="text-align:left; border-bottom:1px solid #f0f0f0;"></th>
        <th style="text-align:left; border-bottom:1px solid #f0f0f0;"></th>
        <th style="text-align:center; border-bottom:1px solid #f0f0f0;"></th>
        <th style="text-align:left; border-bottom:1px solid #f0f0f0;"></th>
    </tr>
    </thead>

    <tbody>
        {{-- 名前 --}}
    <tr style="border-bottom:1px solid #f7f7f7;">
        <td style="color:#666;">名前</td>
        <td colspan="3">
        @if($canEdit)
            <input type="text" value="{{ $name ?? '' }}"
                style="width:100%; height:38px; border:1px solid #ddd; border-radius:6px; padding:0 10px;">
        @else
            <div style="height:38px; border:1px solid #ddd; border-radius:6px; padding:0 10px; display:flex; align-items:center;">
                {{ $name ?? '' }}
            </div>
        @endif
        </td>
    </tr>

        {{-- 日付（Figma風の2カラム表示） --}}
        <tr style="border-bottom:1px solid #f7f7f7;">
        <td style="color:#666;">日付</td>
        <td>
            <div style="height:38px; border:1px solid #ddd; border-radius:6px; padding:0 10px; display:flex; align-items:center;">
            {{ $dateYear ?? '' }}
            </div>
        </td>
        <td></td>
        <td>
            <div style="height:38px; border:1px solid #ddd; border-radius:6px; padding:0 10px; display:flex; align-items:center;">
            {{ $dateMD ?? '' }}
            </div>
        </td>
        </tr>

        {{-- 出勤・退勤 --}}
        <x-time-row label="出勤・退勤"
                    :start="$clockIn ?? ''"
                    :end="$clockOut ?? ''"
                    :mode="$timeMode" />

        {{-- 休憩1 --}}
        <x-time-row label="休憩"
                    :start="$break1In ?? ''"
                    :end="$break1Out ?? ''"
                    :mode="$timeMode" />

        {{-- 休憩2（空欄OK） --}}
        <x-time-row label="休憩2"
                    :start="$break2In ?? ''"
                    :end="$break2Out ?? ''"
                    :mode="$timeMode" />

        {{-- 備考（今回はモック固定。必要ならコントローラから渡す） --}}
        <tr>
        <td style="color:#666;">備考</td>
        <td colspan="3">
            @if($canEdit)
            <textarea rows="3" placeholder="備考を入力"
                style="width:100%; border:1px solid #ddd; border-radius:6px; padding:8px 10px;">{{ $status==='pending' ? '' : '電車遅延のため' }}</textarea>
            @else
            <div style="min-height:38px; border:1px solid #ddd; border-radius:6px; padding:8px 10px;">
                {{ $status==='pending' ? '' : '電車遅延のため' }}
            </div>
            @endif
        </td>
        </tr>
    </tbody>
    </table>

    {{-- 承認待ち注意（スタッフ表示かつ pending の時） --}}
    @if(!$canEdit && ($role === 'staff') && ($status === 'pending'))
    <div style="margin-top:10px; color:#e06; font-size:12px;">
        ※ 承認待ちのため修正はできません。
    </div>
    @endif

    {{-- 右下ボタン：編集可能時のみ --}}
    @if($canEdit)
    <div style="text-align:right; margin-top:12px;">
        <x-button variant="primary">修正</x-button>
    </div>
    @endif
</div>
@endsection