<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>@yield('title','勤怠アプリ')</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="stylesheet" href="{{ asset('css/style.css') }}">

  @stack('head')
</head>
<body>
  {{-- 共通ヘッダー --}}
  <header class="header">
  <div class="container header__inner">
    <div class="brand">
      <img src="{{ asset('images/logo.svg') }}" alt="logo">
      <strong>@yield('header','')</strong>
    </div>

    {{--  ここに各ページのナビが入る（常にヘッダーの右側） --}}
    @yield('nav')
  </div>
</header>

<main class="main @yield('main_class')">
  <div class="container">
    @if(session('success')) <div class="flash">{{ session('success') }}</div> @endif
    @if ($errors->any())
      <div class="danger"><ul class="errors">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    @yield('content')
  </div>
</main>
  <footer>
    <div class="container" style="color:var(--muted); font-size:12px; padding-bottom:24px;">
      &copy; {{ date('Y') }} Attendance App
    </div>
  </footer>

  @stack('body')
</body>
</html>