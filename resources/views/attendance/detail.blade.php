{{-- スタッフ共通レイアウト --}}
@extends('layouts.staff')

@section('title','勤怠詳細')
@section('content')
  <x-page-title>勤怠詳細</x-page-title>


  @include('attendance.partials._detail-card', array_merge(get_defined_vars(), [
    'role'     => 'staff',
    // スタッフは承認待ちの時は編集不可。それ以外は修正申請フォームを出す
    'canEdit'  => ($status ?? 'editable') !== 'pending',
    'footer'   => ($status ?? 'editable') === 'pending' ? 'message' : 'request',
    // 修正申請のPOST先（スタッフ用の store ルート）
    'form'     => ['action' => route('requests.store'), 'method' => 'post'],
  ])
  )
@endsection