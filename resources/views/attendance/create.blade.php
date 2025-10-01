@extends('layouts.staff')
@section('main_class', 'centered')

@section('content')
  <div class="card card--xl">
    <div class="att-badge">
      <span class="badge {{ $badge['class'] }}">{{ $badge['text'] }}</span>
    </div>

    <div class="date-muted">{{ now()->isoFormat('YYYY年M月D日（ddd）') }}</div>
    <div class="big-time">{{ now()->format('H:i') }}</div>

    @if (session('status'))
      <div class="flash" role="alert" style="margin-bottom:16px;">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
      <div class="error" style="margin:0 0 16px;color:red;">
        @foreach ($errors->all() as $msg)
          <div>{{ $msg }}</div>
        @endforeach
      </div>
    @endif

    @switch($state)
      @case('not_working')
        <form method="POST" action="{{ route('attendance.clock-in') }}">
          @csrf
          <x-button type="submit" variant="primary" style="min-width:120px;">出勤</x-button>
        </form>
        @break

      @case('working')
        <div style="display:flex;gap:10px;justify-content:center;margin-top:12px;">
          <form method="POST" action="{{ route('attendance.break-in') }}">@csrf
            <x-button type="submit" variant="primary">休憩入</x-button>
          </form>
          <form method="POST" action="{{ route('attendance.clock-out') }}">@csrf
            <x-button type="submit" variant="primary">退勤</x-button>
          </form>
        </div>
        @break

      @case('on_break')
        <div style="display:flex;gap:10px;justify-content:center;margin-top:12px;">
          <form method="POST" action="{{ route('attendance.break-out') }}">@csrf
            <x-button type="submit" variant="primary">休憩戻</x-button>
          </form>
          <form method="POST" action="{{ route('attendance.clock-out') }}">@csrf
            <x-button type="submit" variant="primary">退勤</x-button>
          </form>
        </div>
        @break

      @case('closed')
        <div style="margin-top:12px;">お疲れさまでした。</div>
        @break
    @endswitch
  </div>
@endsection