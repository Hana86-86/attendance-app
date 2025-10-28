@extends('layouts.admin')

@section('content')

@php
  $isDetail = (bool)($isDetail ?? false);
@endphp

@if($isDetail)
  <x-page-title>勤怠詳細</x-page-title>
@else
  <x-page-title>{{ $title ?? '勤怠一覧' }}</x-page-title>

  @php
    $_date    = $date ?? now()->toDateString();
    $_prev    = \Carbon\Carbon::parse($_date)->subDay()->toDateString();
    $_next    = \Carbon\Carbon::parse($_date)->addDay()->toDateString();
  @endphp

  {{-- ← 前日 / 見出し日付 / 翌日 → --}}
  <div class="card att-topbar" style="display:flex;gap:8px;align-items:center;justify-content:space-between;margin-bottom:12px;">
    <a class="btn" href="{{ route('admin.attendances.index', ['date' => $_prev]) }}">前日</a>

    <div style="flex:1; text-align:center; font-weight:600;">
      {{ \Carbon\Carbon::parse($_date)->isoFormat('YYYY/MM/DD (ddd)') }}
    </div>

    <a class="btn" href="{{ route('admin.attendances.index', ['date' => $_next]) }}">翌日</a>
  </div>
@endif


{{-- ===== 本文：詳細 or 一覧 ===== --}}
@if($isDetail)
  @include('attendance.partials._detail-card', get_defined_vars())
@else
  {{--  一覧テーブル --}}
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
@forelse($list as $att)
  @php
    $detailUrl = route('admin.attendances.show', ['date' => $date, 'id' => $att->user_id]);
  @endphp
  <tr>
    <td class="text-left">{{ optional($att->user)->name ?? '' }}</td>
    <td class="mono">{{ $att->clock_in?->format('H:i')  ?? '—' }}</td>
    <td class="mono">{{ $att->clock_out?->format('H:i') ?? '—' }}</td>
    <td class="mono">{{ $att->break_hm }}</td>  {{-- モデルの表示用アクセサ --}}
    <td class="mono">{{ $att->work_hm  }}</td>  {{-- モデルの表示用アクセサ --}}
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