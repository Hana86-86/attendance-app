@extends('layouts.admin')

@section('content')
<x-page-title>{{ $title ?? '勤怠一覧' }}</x-page-title>

@php
    $__date     = $date     ?? now()->toDateString();
    $__prevDate = $prevDate ?? \Carbon\Carbon::parse($__date)->subDay()->toDateString();
    $__nextDate = $nextDate ?? \Carbon\Carbon::parse($__date)->addDay()->toDateString();
@endphp

{{-- ツールバー（前日 / 日付（カレンダー） / 翌日） --}}
<div class="card att-toolbar" style="display:flex;gap:8px;align-items:center;">
    <a class="btn" href="{{ route('admin.attendances.index', ['date' => $__prevDate]) }}">← 前日</a>

    <div style="flex:1;text-align:center;font-weight:600;display:flex;justify-content:center;align-items:center;gap:8px;">
    {{-- カレンダーアイコン（SVG） --}}
    <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" fill="none" stroke="currentColor"/>
        <line x1="3" y1="10" x2="21" y2="10" stroke="currentColor"/>
        <line x1="8" y1="2" x2="8" y2="6" stroke="currentColor"/>
        <line x1="16" y1="2" x2="16" y2="6" stroke="currentColor"/>
    </svg>
    {{ \Carbon\Carbon::parse($__date)->isoFormat('YYYY/MM/DD (ddd)') }}
    </div>

    <a class="btn" href="{{ route('admin.attendances.index', ['date' => $__nextDate]) }}">翌日 →</a>
</div>

@isset($role)
    @include('attendance.partials._detail-card', get_defined_vars())
@else
    {{-- 一覧テーブル --}}
    <div class="card">
        <table class="table att-table">
        <thead>
        <tr>
            <th class="text-left" style="width:160px;">名前</th>
            <th>出勤</th>
            <th>退勤</th>
            <th>休憩</th>
            <th>合計</th>
            <th style="width:98px;">詳細</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($list ?? [] as $row)
            @php
                $detailDate = \Carbon\Carbon::parse($row['work_date'] ?? $__date)->format('Y-m-d');
            @endphp
            <tr>
                <td class="text-left">{{ $row['name'] }}</td>
                <td class="mono">{{ $row['clock_in']  ?: '' }}</td>
                <td class="mono">{{ $row['clock_out'] ?: '' }}</td>
                <td class="mono">{{ m2hm($row['break_min'] ?? 0) }}</td>
                <td class="mono">{{ m2hm($row['work_min']  ?? 0) }}</td>
                <td class="mono">
                <a class="btn btn-link"
                    href="{{ route('admin.attendances.show', ['date' => $detailDate, 'id' => $row['user_id']]) }}">
                    詳細
                </a>
            </td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">表示できるデータがありません。</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
@endisset
@endsection