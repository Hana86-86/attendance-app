@extends('layouts.guest')

@section('title', 'メール認証のお願い')

@section('content')
<div class="card">
    <h1 style="font-size:20px;margin:0 0 12px;">メール認証</h1>

    @if (session('status') === 'verification-link-sent')
        <div class="flash">認証メールを再送しました。受信トレイをご確認ください。</div>
    @endif

    <p class="muted" style="margin:0 0 16px;">
        登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。
    </p>

    {{-- ボタン版（同じアクション） --}}
    <form method="POST" action="{{ route('verification.send') }}" style="margin:12px 0">
    @csrf
    <button type="submit" class="btn" style="width:100%;text-align:center">
        認証はこちらから
    </button>
    </form>

    {{-- テキストリンク版（同じアクション） --}}
    <form method="POST" action="{{ route('verification.send') }}" style="text-align:center;margin-top:8px">
        @csrf
        <button type="submit" class="btn-link">認証メールを再送する</button>
    </form>
</div>
@endsection