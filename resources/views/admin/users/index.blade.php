@extends('layouts.admin')

@section('content')
  <x-page-title>スタッフ一覧</x-page-title>

  <div class="card">
    <table class="table" style="width:100% table-layout: fixed; text-align:center;">
      <thead>
        <tr>
          <th style="width:33%;">名前</th>
          <th style="width:33%;">メールアドレス</th>
          <th style="width:34%;">月次勤怠</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($users as $u)
          <tr>
            <td>{{ $u->name }}</td>
            <td>{{ $u->email }}</td>
            <td>
              <a class="btn btn-link"
                  href="{{ route('admin.users.attendances', ['id' => $u->id, 'month' => $month]) }}">
                詳細
              </a>
            </td>
          </tr>
        @empty
          <tr><td colspan="3" style="text-align:center;color:#666;">スタッフが登録されていません。</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
@endsection