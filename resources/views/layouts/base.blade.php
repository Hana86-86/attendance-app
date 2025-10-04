<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>@yield('title','勤怠アプリ')</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <style>
    /* =========================
        ベース（色・幅・余白の変数）
       ========================= */
    :root{
      --bg:#f1f1f3;         /* 背景グレー */
      --panel:#ffffff;      /* パネル白 */
      --ink:#111;           /* 文字色 */
      --muted:#707070;      /* 補助文字 */
      --brand:#111;         /* ヘッダー色 */
      --accent:#111;        /* 強調（ボタン/アクティブ線） */
      --radius:12px;        /* 角丸 */
      --gutter:20px;        /* スマホ左右余白 */
      --gutter-lg:28px;     /* PC左右余白 */

      /* コンテンツの最大幅（960〜1320で可変） */
      --wrap: clamp(960px, 92vw, 1320px);
    }

    * { box-sizing: border-box; }
    html,body{
      margin:0; padding:0;
      background:var(--bg); color:var(--ink);
      font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto,
                    "ヒラギノ角ゴ ProN","Hiragino Kaku Gothic ProN","Noto Sans JP",
                    "メイリオ", Meiryo, sans-serif;
      -webkit-text-size-adjust:100%;
    }
    img{ max-width:100%; height:auto; }
    a{ color:inherit; text-decoration:none; }

    /* =========================
        共通コンテナ（幅統一）
       ========================= */
    .container{
      max-width: var(--wrap);
      margin: 0 auto;
      padding: 0 var(--gutter);
    }
    @media (min-width:1024px){
      .container{ padding: 0 var(--gutter-lg); }
    }

    /* =========================
        ヘッダー
       ========================= */
    header.header{ background:var(--brand); color:#fff; }
    .header__inner{
      height:64px;
      display:flex; align-items:center; gap:24px;
    }
    .brand{ display:flex; align-items:center; gap:12px; }
    .brand img{ height:24px; display:block; }
    .brand strong{ font-weight:700; letter-spacing:.08em; }

    /* ナビ */
    nav.nav{ margin-left:auto; display:flex; gap:22px; flex-wrap:wrap; }
    nav.nav a{ color:#fff; opacity:.9; padding:6px 4px; position:relative; }
    nav.nav a.active{ opacity:1; }
    nav.nav a.active::after{
      content:""; position:absolute; left:0; right:0; bottom:-6px;
      height:2px; background:#fff; border-radius:2px;
    }

    /* =========================
        本文
       ========================= */
    main.main{ padding:28px 0 60px; }

    /* 見出しバー */
    .page-head{ margin:24px 0 16px; display:flex; align-items:center; gap:12px; }
    .page-head .bar{ width:4px; height:22px; background:var(--ink); border-radius:2px; }
    .page-head h1{ font-size:20px; margin:0; }

    /* パネル / カード */
    .card{
      background:var(--panel); border-radius:var(--radius); padding:22px;
      box-shadow: 0 1px 0 rgba(0,0,0,.05), 0 10px 24px rgba(0,0,0,.05);
    }

    /* テーブル（横スクロール逃し） */
    .table-wrap{ overflow-x:auto; -webkit-overflow-scrolling:touch; }
    .table{
      width:100%; border-collapse:separate; border-spacing:0;
      background:#fff; border-radius:12px;
    }
    .table th, .table td{
      padding:12px 14px; border-bottom:1px solid #eee; font-size:14px; white-space:nowrap;
    }
    .table thead th{ background:#fafafa; font-weight:700; }
    .table tr:last-child td{ border-bottom:none; }

    /* フォーム */
    .field{ display:grid; gap:6px; }
    input, select, textarea{
      width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:10px; background:#fff; min-width:0;
    }
    input.is-invalid, select.is-invalid, textarea.is-invalid{ border-color:#a40000; background:#fff7f7; }
    p.error{ color:#a40000; font-size:14px; margin:6px 0 0; }

    /* ボタン */
    .btn{
      display:inline-block; padding:10px 18px; border-radius:10px;
      border:1px solid var(--accent); background:#fff; color:var(--ink);
      cursor:pointer; transition:.15s;
    }
    .btn.primary{ background:var(--accent); color:#fff; }
    .btn:hover{ transform: translateY(-1px); }

    /* メッセージ */
    .flash{ background:#fff0f0; border:1px solid #f0c7c7; padding:10px 12px; border-radius:10px; }
    .danger{ background:#fff5f5; border:1px solid #ffd2d2; color:#a40000; padding:8px 12px; border-radius:10px; }
    ul.errors{ margin:6px 0 18px; }


    /* 低速アニメ派にはやさしく */
    @media (prefers-reduced-motion: reduce){
      *{ transition:none !important; animation:none !important; }
    }
    /* 画面全体を中央寄せにしたいページ用のスイッチ */
  .main.centered {
    min-height: calc(100vh - 64px - 48px);
    display:flex;
    align-items:center;
    justify-content:center;
    padding: 28px 0;
  }

 /* ====== Figmaトーンの最小スタイル ====== */
.card.--centered{
  max-width: 760px; margin: 40px auto; padding: 40px;
  background: #f7f7f7; border: 1px solid #e5e5e5;
}

.state-badge{ text-align:center; margin-bottom:16px; }
.badge{
  display:inline-block; font-size:12px; line-height:1; padding:6px 10px;
  background:#E5E7EB; color:#333; border-radius:9999px;
}

.date-meta{ text-align:center; color:#666; }
.date-meta .y{ font-size:14px; margin-bottom:2px; }
.date-meta .md{ font-size:14px; }

.big-time{
  text-align:center; font-weight:700; font-size:40px; margin:10px 0 24px;
}

.alert{ margin:10px auto 0; max-width:420px; text-align:center; color:#0a0; }
.error{ margin:10px auto 0; max-width:420px; text-align:center; color:#d00; }

.actions{ display:flex; justify-content:center; gap:20px; margin-top:6px; }
.actions.--single{ justify-content:center; }
.actions.--double{ justify-content:center; }

.btn{
  display:inline-block; min-width:140px; padding:10px 16px;
  font-weight:700; border-radius:6px; border:1px solid transparent; cursor:pointer;
}
.btn-primary{
  background:#111; color:#fff; border-color:#111;
}
.btn-primary:hover{ opacity:.9; }
.btn-secondary{
  background:#e6e6e6; color:#111; border-color:#d9d9d9;
}
.btn-secondary:hover{ background:#dcdcdc; }

.finished{
  margin-top:20px; text-align:center; color:#333;
}
/* カード内上部のバッジ位置 */
  .att-badge{ margin-bottom: 10px; }

  /* トップの日付ナビ */
.att-toolbar{
  display:flex; gap:8px; align-items:center; justify-content:space-between;
  padding:8px 12px; margin-bottom:12px;
}
.att-date{ height:36px; padding:0 10px; }

/* Figmaっぽいコンパクトテーブル */
.att-table{
  width:100%; table-layout:fixed; border-collapse:collapse; text-align:center;
}
.att-table thead th{
  background:#f7f7f9; color:#333; font-weight:600;
  padding:10px 16px; border-bottom:1px solid #e5e7eb;
}
.att-table tbody td{
  padding:12px 16px; border-bottom:1px solid #eaecef;
}
.att-table th:nth-child(1), .att-table td:nth-child(1){ width:34%; }
.att-table th:nth-child(2), .att-table td:nth-child(2),
.att-table th:nth-child(3), .att-table td:nth-child(3),
.att-table th:nth-child(4), .att-table td:nth-child(4),
.att-table th:nth-child(5), .att-table td:nth-child(5){ width:11%; }
.att-table th:last-child, .att-table td:last-child{ width:10%; }

.text-left{ text-align:left; }
.mono{ font-variant-numeric: tabular-nums; white-space:nowrap; }
.empty{ text-align:center; color:#666; }



 </style>

  @stack('head') {{-- 画面ごとの追記用 --}}
</head>
<body>

  {{-- 黒い共通ヘッダー --}}
  <header class="header">
    <div class="container header__inner">
      <div class="brand">
        <img src="{{ asset('images/logo.svg') }}" alt="logo">
        <strong>@yield('header','')</strong>
      </div>
      {{-- ここに各レイアウトがナビを差し込む --}}
      @yield('nav')
    </div>
  </header>

  <main class="main @yield('main_class')">
    <div class="container">
      {{-- フラッシュ/エラー（FormRequestやセッション用） --}}
      @if(session('success'))
        <div class="flash">{{ session('success') }}</div>
      @endif
      @if ($errors->any())
        <div class="danger">
          <ul class="errors">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      {{-- 画面固有の中身 --}}
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