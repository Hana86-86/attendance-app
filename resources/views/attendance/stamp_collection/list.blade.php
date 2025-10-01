@extends('layouts.staff')

@section('title', $status === 'approved' ? '申請一覧（承認済み）' : '申請一覧（承認待ち）')

@section('nav')
  <x-page-title>申請一覧</x-page-title>
  <nav class="nav">
    <a href="{{ route('requests.list', ['status'=>'pending']) }}"
        class="{{ $status==='pending' ? 'active' : '' }}">承認待ち</a>
    <a href="{{ route('requests.list', ['status'=>'approved']) }}"
        class="{{ $status==='approved' ? 'active' : '' }}">承認済み</a>
  </nav>
@endsection

@section('content')
<x-page-title>申請一覧</x-page-title>

<div class="card" style="max-width:768px;margin:auto;">
  <table class="table">
    <thead>
      <tr>
        <th>申請日</th>
        <th>対象日</th>
        <th>出勤</th>
        <th>退勤</th>
        <th>休憩</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    @forelse ($list as $r)
      @php
        $p = $r->payload ?? [];
        $date = $p['date'] ?? null;
        $ci   = $p['clock_in']  ?? '';
        $co   = $p['clock_out'] ?? '';
        $breaks = is_countable($p['breaks'] ?? null) ? count($p['breaks']) : 0;
      @endphp
      <tr>
        <td>{{ \Carbon\Carbon::parse($r->created_at)->format('Y/m/d H:i') }}</td>
        <td>{{ $date ? \Carbon\Carbon::parse($date)->format('Y/m/d') : '' }}</td>
        <td class="mono">{{ $ci }}</td>
        <td class="mono">{{ $co }}</td>
        <td class="mono">{{ $breaks }}</td>
        <td><a class="btn btn-link" href="{{ route('requests.show', ['id'=>$r->id]) }}">詳細</a></td>
      </tr>
    @empty
      <tr><td colspan="6" class="empty">対象の申請はありません</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
@endsection