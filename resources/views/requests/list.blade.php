@extends('layouts.staff')

@section('content')
  <x-page-title>申請一覧</x-page-title>

  @php
    // URL ?status=… が不正でも安全に
    $status = in_array(request('status'), ['pending','approved']) ? request('status') : 'pending';
  @endphp

  {{-- ページ内タブ（Figmaの「承認待ち / 承認済み」） --}}
  <nav class="page-tabs">
    <a href="{{ route('requests.list', ['status' => 'pending']) }}"
        class="{{ $status === 'pending' ? 'is-active' : '' }}">承認待ち</a>
    <a href="{{ route('requests.list', ['status' => 'approved']) }}"
        class="{{ $status === 'approved' ? 'is-active' : '' }}">承認済み</a>
  </nav>

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
        @forelse ($list ?? [] as $r)
          @php
            $statusLabel = $r->status === 'approved' ? '承認済み' : '承認待ち';
            $targetDate  = optional($r->attendance?->work_date)->format('Y/m/d') ?? '—';
            $reason      = $r->reason ?: '—';
            $requestedAt = optional($r->created_at)->format('Y/m/d H:i') ?: '—';
            $detailDate  = optional($r->attendance?->work_date)->format('Y-m-d');
            // スタッフの詳細は自分の勤怠詳細へ
            $detailUrl   = $detailDate ? route('attendance.detail', ['date' => $detailDate]) : null;
          @endphp
          <tr>
            <td>{{ $statusLabel }}</td>
            <td>{{ auth()->user()->name }}</td>
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
@endsection