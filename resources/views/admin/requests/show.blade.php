@extends('layouts.admin')

@section('content')
<x-page-title>申請詳細</x-page-title>

<div class="card" style="max-width:780px">
  <table class="table">
    <tr><th>申請者</th><td>{{ $req->user->name }}</td></tr>
    <tr><th>対象日</th><td>{{ optional($req->attendance)->work_date ?? '-' }}</td></tr>
    <tr><th>出勤</th><td>{{ $req->requested_clock_in  ?? '--:--' }}</td></tr>
    <tr><th>退勤</th><td>{{ $req->requested_clock_out ?? '--:--' }}</td></tr>
    <tr><th>備考</th><td>{{ $req->note }}</td></tr>
    <tr><th>状態</th><td>{{ $req->status }}</td></tr>
  </table>

  @if ($req->status === 'pending')
    <form method="post" action="{{ route('admin.requests.approve',['id'=>$req->id]) }}" style="margin-top:16px">
      @csrf
      <x-button type="submit" variant="primary">承認</x-button>
    </form>
  @else
    <p style="margin-top:16px;color:#666">この申請は {{ $req->status }} です。</p>
  @endif
</div>

<a class="btn" href="{{ route('admin.requests.index',['status'=>'pending']) }}" style="margin-top:12px">一覧へ</a>
@endsection