@extends('layouts.admin')

@section('content')
  <x-page-title>スタッフ一覧</x-page-title>

  <div class="table-wrap">
    <div class="card">
      <table class="table">
        <thead>
          <tr>
            <th class="w-34 text-left">名前</th>
            <th class="w-34 text-left">メールアドレス</th>
            <th class="w-22">月次集計</th>
            <th class="w-10"></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($users as $u)
            <tr>
              <td class="text-left">{{ $u->name }}</td>
              <td class="text-left">{{ $u->email }}</td>
              <td class="mono">-</td>
              <td>
                <a class="btn btn-link"
                    href="{{ route('admin.users.attendances', ['id' => $u->id, 'month' => $month]) }}">
                  詳細
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="empty">スタッフが登録されていません。</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection