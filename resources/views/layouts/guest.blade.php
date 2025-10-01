<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
<style>
    body{background:#111;color:#222;margin:0;font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto;}
    .wrap{max-width:960px;margin:0 auto;padding:24px;}
    .brand{display:flex;align-items:center;height:56px;background:#000;color:#fff;padding:0 16px;}
    .card{max-width:560px;margin:40px auto;background:#fff;border-radius:12px;padding:24px;box-shadow:0 4px 24px rgba(0,0,0,.12);}
    .btn{display:inline-block;padding:10px 18px;border-radius:8px;border:1px solid #111;background:#111;color:#fff;text-decoration:none;cursor:pointer}
    .btn-link{border:none;background:transparent;color:#666;cursor:pointer;text-decoration:underline}
    .muted{color:#888;font-size:.9rem}
    .flash{background:#eef7ff;border:1px solid #b3dcff;border-radius:8px;padding:10px;margin:12px 0}
</style>
</head>
<body>
<header class="brand">
    <img src="{{ asset('images/logo.svg') }}" alt="logo" style="height:22px">
</header>

<main class="wrap">
    @yield('content')
</main>
</body>
</html>