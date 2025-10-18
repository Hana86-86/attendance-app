@extends('layouts.admin')

@section('content')
{{-- 見出し：◯◯さんの勤怠 --}}
<x-page-title>{{ ($user->name ?? 'スタッフ') }}さんの勤怠</x-page-title>

@php
    $__month     = $month     ?? now()->format('Y-m');               // '2025-10'
    $__prevMonth = $prevMonth ?? \Carbon\Carbon::parse($__month.'-01')->subMonth()->format('Y-m');
    $__nextMonth = $nextMonth ?? \Carbon\Carbon::parse($__month.'-01')->addMonth()->format('Y-m');
    $__ymLabel   = \Carbon\Carbon::parse($__month.'-01')->isoFormat('YYYY/MM'); // 中央表示用
    $__userId    = $user->id ?? ($user_id ?? null);
@endphp

{{-- 月移動ツールバー（前月 / カレンダー+年月 / 翌月） --}}
<div class="card att-toolbar" style="display:flex;gap:8px;align-items:center;">
    <a class="btn" href="{{ route('admin.users.attendances', ['id' => $__userId, 'month' => $__prevMonth]) }}">← 前月</a>

    <div style="flex:1;text-align:center;font-weight:600;display:flex;justify-content:center;align-items:center;gap:8px;">
    <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" fill="none" stroke="currentColor"/>
        <line x1="3" y1="10" x2="21" y2="10" stroke="currentColor"/>
        <line x1="8" y1="2" x2="8" y2="6" stroke="currentColor"/>
        <line x1="16" y1="2" x2="16" y2="6" stroke="currentColor"/>
    </svg>
        {{ $__ymLabel }}
    </div>

    <a class="btn" href="{{ route('admin.users.attendances', ['id' => $__userId, 'month' => $__nextMonth]) }}">翌月 →</a>
</div>

{{-- 月次一覧テーブル（日付 / 出勤 / 退勤 / 休憩 / 合計 / 詳細） --}}
<div class="card">
    <table class="table att-table">
    <thead>
        <tr>
            <th class="text-left" style="width:140px;">日付</th>
            <th>出勤</th>
            <th>退勤</th>
            <th>休憩</th>
            <th>合計</th>
            <th style="width:98px;">詳細</th>
        </tr>
    </thead>
    <tbody>
        @forelse(($list ?? []) as $row)
        @php
            // Controller 側で 'work_date' or 'date' を詰めている想定。なければ月1日で保護
            $ymd = $row['work_date'] ?? $row['date'] ?? ($__month.'-01');
            $dLabel = \Carbon\Carbon::parse($ymd)->isoFormat('MM/DD(ddd)'); // Figmaっぽい表記
            $detailUrl = route('admin.attendances.show', [
            'date' => \Carbon\Carbon::parse($ymd)->format('Y-m-d'),
            'id'   => $__userId,
            ]);
        @endphp
        <tr>
            <td class="text-left mono">{{ $dLabel }}</td>
            <td class="mono">{{ $row['clock_in']  ?? '' }}</td>
            <td class="mono">{{ $row['clock_out'] ?? '' }}</td>
            <td class="mono">{{ $row['break_hm'] ?? '—' }}</td>
            <td class="mono">{{ $row['work_hm']  ?? '—' }}</td>
            <td>
                <a class="btn" href="{{ $detailUrl }}">詳細</a>
            </td>
        </tr>
        @empty
            <tr><td colspan="6" class="empty">表示できるデータがありません。</td></tr>
        @endforelse
    </tbody>
    </table>
    <div style="text-align:right; margin-top:16px;">
    <a class="btn" href="{{ route('admin.users.attendances.csv', ['id' => $user->id, 'month' => $month]) }}">
    CSV出力
    </a>
</div>
</div>
@endsection