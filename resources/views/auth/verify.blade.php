@extends('layouts.guest')

@section('title', 'メール認証')

@section('content')
<div style="max-width:760px; margin:80px auto; text-align:center;">

    {{-- 説明文 --}}
    <p class="muted" style="margin:0 0 28px; line-height:1.8;">
        登録していただいたメールアドレスに認証メールを送付しました。<br>
        メール認証を完了してください。
    </p>

    {{-- 成功トースト（再送直後の通知） --}}
    @if (session('status') === 'verification-link-sent')
    <div class="alert" style="margin:0 auto 20px; color:#0a7; font-size:14px;">
        認証メールを再送しました。受信トレイをご確認ください。
    </div>
    @endif

    {{-- 大きいボタン（Figmaの「認証はこちらから」） --}}
    <form method="POST" action="{{ route('verification.send') }}">
    @csrf
    <button type="submit"
            class="btn"
            style="width:320px; height:44px; font-weight:700;">
        認証はこちらから
    </button>
    </form>

    {{-- 再送 --}}
    <form method="POST" action="{{ route('verification.send') }}"
            style="margin-top:18px;">
    @csrf
    <button type="submit"
            class="btn btn-link"
            style="font-size:14px;">
        認証メールを再送する
    </button>
    </form>

</div>
@endsection