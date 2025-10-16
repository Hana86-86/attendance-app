@extends('layouts.admin')

@section('content')
<x-page-title>スタッフ一覧</x-page-title>

<table class="table at-table">
  <thead>
    <tr>
      <th class="text-left">名前</th>
      <th class="text-left">メールアドレス</th>
      <th class="text-right" style="width:120px;">月次勤怠</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($users as $u)
      <tr>
        <td class="text-left">{{ $u->name }}</td>
        <td class="text-left mono">{{ $u->email }}</td>
        <td class="text-right">
          <a class="btn btn-link"
              href="{{ route('admin.users.attendances', ['id' => $u->id, 'month' => $month]) }}">
            詳細
          </a>
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="3" class="empty">スタッフが登録されていません。</td>
      </tr>
    @endforelse
  </tbody>
</table>
@endsection