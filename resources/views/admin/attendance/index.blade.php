@extends('layouts.admin')

@section('content')
<x-page-title>{{ $title }}</x-page-title>

<div class="card att-toolbar">
    <a class="btn" href="{{ route('admin.attendances.index', ['date' => $prevDate]) }}">← 前日</a>
    <div style="flex:1;text-align:center;font-weight:600;">{{ \Carbon\Carbon::parse($date)->isoFormat('YYYY/MM/DD (ddd)') }}</div>
    <a class="btn" href="{{ route('admin.attendances.index', ['date' => $nextDate]) }}">翌日 →</a>
</div>

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
    @forelse ($list as $row)
    <tr>
        <td class="text-left">{{ $row['name'] }}</td>
        <td class="mono">{{ $row['clock_in']  ?: '' }}</td>
        <td class="mono">{{ $row['clock_out'] ?: '' }}</td>
        <td class="mono">{{ m2hm($row['break_min']) }}</td>
        <td class="mono">{{ m2hm($row['work_min']) }}</td>
        @php
            $detailDate = \Carbon\Carbon::parse($row['work_date'])->format('Y-m-d');
        @endphp

    <td class="mono">
    <a class="btn btn-link" href="{{ route('admin.attendances.show', ['date' => $detailDate, 'id' => $row['user_id']]) }}">
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
@endsection