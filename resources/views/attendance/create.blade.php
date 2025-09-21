

<h1>本日の出勤</h1>

@if (session('success')) <p>{{ session('success') }}</p> @endif
@error('state') <p style="color:red">{{ $message }}</p> @enderror

@if(!$attendance)
  <form method="post" action="{{ route('attendance.clockin') }}">@csrf
    <button>出勤する</button>
  </form>

@elseif($attendance->status === 'working')
  @php $onBreak = optional($attendance->breakTimes->last())->end === null; @endphp

  @if($onBreak)
    <form method="post" action="{{ route('attendance.breakout') }}">@csrf
      <button>休憩から戻る</button>
    </form>
  @else
    <form method="post" action="{{ route('attendance.clockout') }}">@csrf
      <button>退勤する</button>
    </form>
    <form method="post" action="{{ route('attendance.breakin') }}">@csrf
      <button>休憩に入る</button>
    </form>
  @endif

@else
  <p>お疲れさまでした！</p>
@endif
