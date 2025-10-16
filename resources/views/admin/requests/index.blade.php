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

@if(!empty($detail))
    {{-- 詳細モード（同ページ表示） --}}
    @include('attendance.partials._detail-card', array_merge($detailVars, [
    'role'    => 'admin',
    'canEdit' => true,
    'footer'  => (($status ?? '') === 'approved') ? 'approved' : 'approve',
    'form'    => ['action' => route('admin.requests.approve'), 'method' => 'post'],
    'detailId'=> $detail->id ?? null,   {{-- 承認POST用 hidden に利用 --}}
    ]))
@else
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
        @forelse($list ?? [] as $r)
            @php
                $statusLabel = $r->status === 'approved' ? '承認済み' : '承認待ち';
                $targetDate  = optional($r->attendance?->work_date)->format('Y/m/d') ?? '—';
                $reason      = $r->reason ?: '—';
                $requestedAt = optional($r->created_at)->format('Y/m/d H:i') ?? '—';
                $canShow     = $r->attendance && $r->user_id && $r->attendance->work_date;
                $detailUrl   = $canShow ? route('admin.requests.index', ['status' => $status, 'id' => $r->id]) : null;
            @endphp
            <tr>
                <td>{{ $statusLabel }}</td>
                <td>{{ $r->user->name ?? '—' }}</td>
                <td class="mono">{{ $targetDate }}</td>
                <td>{{ $reason }}</td>
                <td class="mono">{{ $requestedAt }}</td>
                <td>
                @if($detailUrl)
                    <a class="btn btn-link" href="{{ $detailUrl }}">詳細</a>
                @else
                    <span class="btn btn-disabled" aria-disabled="true">詳細</span>
                @endif
            </td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">申請はありません</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
    @endif
@endsection