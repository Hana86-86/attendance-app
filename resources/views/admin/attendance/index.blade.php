@extends('layouts.admin')

@section('content')

@php
  // 詳細モードの判定（Controller@show から $role='admin' が来ている時）
  $isDetail = isset($role) && $role === 'admin';
@endphp

{{-- ===== ページタイトル・上部UI ===== --}}
@if($isDetail)
  {{-- ▼ 勤怠詳細（カレンダーバーは出さない） --}}
  <x-page-title>勤怠詳細</x-page-title>

@else
  {{-- ▼ 勤怠一覧（タイトル＋カレンダーバーを出す） --}}
  <x-page-title>{{ $title ?? '勤怠一覧' }}</x-page-title>

  @php
    $_date    = $date ?? now()->toDateString();
    $_prev    = \Carbon\Carbon::parse($_date)->subDay()->toDateString();
    $_next    = \Carbon\Carbon::parse($_date)->addDay()->toDateString();
  @endphp

  {{-- ← 前日 / 見出し日付 / 翌日 → --}}
  <div class="card att-topbar" style="display:flex;gap:8px;align-items:center;justify-content:space-between;margin-bottom:12px;">
    <a class="btn" href="{{ route('admin.attendances.index', ['date' => $_prev]) }}">前日</a>

    <div style="flex:1;text-align:center;font-weight:600;display:flex;justify-content:center;align-items:center;gap:8px;">
      <svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M7 10h5v5H7zM14 10h3v5h-3z" fill="currentColor"/>
      </svg>
      <span>{{ \Carbon\Carbon::parse($_date)->isoFormat('YYYY/MM/DD (ddd)') }}</span>
    </div>

    <a class="btn" href="{{ route('admin.attendances.index', ['date' => $_next]) }}">翌日</a>
  </div>
@endif


{{-- ===== 本文：詳細 or 一覧 ===== --}}
@if($isDetail)
  {{-- ▼ 詳細カード（右下に修正/承認/承認済み） --}}
  @include('attendance.partials._detail-card', get_defined_vars())

@else
  {{-- ▼ 一覧テーブル（従来どおり） --}}
  <div class="card">
    <table class="table att-table">
      <thead>
        <tr>
          <th class="text-left" style="width:160px;">名前</th>
          <th>出勤</th>
          <th>退勤</th>
          <th>休憩</th>
          <th>休憩2</th>
          <th style="width:98px;">詳細</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($list ?? [] as $row)
          @php
            $s_date    = $date ?? now()->toDateString();
            $detailUrl = route('admin.attendances.show', [
              'date' => $s_date,
              'id'   => $row['user_id'],
            ]);
          @endphp
          <tr>
            <td class="text-left">{{ $row['name'] ?? '' }}</td>
            <td class="mono">{{ $row['clock_in']  ?? '—' }}</td>
            <td class="mono">{{ $row['clock_out'] ?? '—' }}</td>
            <td class="mono">{{ \Illuminate\Support\Arr::get($row,'break_min', '—') }}</td>
            <td class="mono">{{ \Illuminate\Support\Arr::get($row,'break2_min','—') }}</td>
            <td class="text-right">
              <a class="btn btn-link" href="{{ $detailUrl }}">詳細</a>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="empty">表示できるデータがありません。</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endif

@endsection