@extends('layouts.admin')

@section('content')
<x-page-title>申請一覧</x-page-title>

{{-- ページ内タブ（承認待ち / 承認済み） --}}
<nav class="page-tabs">
    <a href="{{ route('admin.requests.index', ['status' => 'pending']) }}"
        class="{{ ($status ?? 'pending') === 'pending' ? 'is-active' : '' }}">承認待ち</a>
    <a href="{{ route('admin.requests.index', ['status' => 'approved']) }}"
        class="{{ ($status ?? '') === 'approved' ? 'is-active' : '' }}">承認済み</a>
</nav>


    {{-- 一覧モード --}}
    <div class="card">
    <table class="table">
        <thead>
        <tr>
            <th>状態</th>
            <th>名前</th>
            <th>対象日付</th>
            <th>申請理由</th>
            <th>申請日時</th>
            <th>詳細</th>
        </tr>
        </thead>
        <tbody>
@forelse ($list ?? [] as $sr)
    @php
    $statusLabel = $sr->status === 'approved' ? '承認済み' : '承認待ち';

    $workDate = optional($sr->attendance?->work_date)?->toDateString()
            ?? \Carbon\Carbon::parse($sr->requested_clock_in ?? $sr->requested_clock_out ?? now())->toDateString();

    // 勤怠詳細へ遷移
    $detailUrl = route('admin.attendances.show', [
        'date' => $workDate,
        'id'   => $sr->user_id,
        'source' => 'requests',
]);
    $displayDate = \Carbon\Carbon::parse($workDate)->isoFormat('YYYY/MM/DD');
    $requestedAt = optional($sr->created_at)?->format('Y/m/d H:i');
@endphp

<tr>
    <td>{{ $statusLabel }}</td>
    <td class="nowrap">{{ $sr->user->name ?? '' }}</td>
    <td class="nowrap">{{ $displayDate }}</td>
    <td class="nowrap">{{ $sr->reason ?? '' }}</td>
    <td class="nowrap">{{ $requestedAt }}</td>
    <td><a class="btn btn-link" href="{{ $detailUrl }}">詳細</a></td>
</tr>
@empty
    <tr><td colspan="6" class="empty">申請はありません。</td></tr>
@endforelse
</tbody>
    </table>
    </div>
@endsection