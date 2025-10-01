@extends('layouts.admin')

@section('content')
  <x-page-title>申請一覧</x-page-title>

  <div class="card">
    <table class="table">
      <thead>
        <tr><th>状態</th><th>名前</th><th>対象日</th><th>申請理由</th><th>申請日時</th><th></th></tr>
      </thead>
      <tbody>
        @for($i=1;$i<=10;$i++)
          <tr>
            <td>承認待ち</td><td>西 伶奈</td><td>2023/06/0{{ $i }}</td><td>遅延のため</td><td>2023/06/02</td>
            <td><x-button as="a" href="/_mock/admin/requests/{{ $i }}">詳細</x-button></td>
          </tr>
        @endfor
      </tbody>
    </table>
  </div>
@endsection