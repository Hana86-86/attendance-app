@extends('layouts.base')

@section('nav') @endsection

@section('content')
<x-page-title>管理者ログイン</x-page-title>

<div class="card">
    <form method="POST" action="{{ route('admin.login.post') }}">
    @csrf

    <div class="field">
        <label>メールアドレス</label>
        <input class="input {{ $errors->has('email') ? 'is-invalid' : '' }}"
            type="email" name="email" value="{{ old('email') }}">
        @error('email') <p class="error">{{ $message }}</p> @enderror
    </div>

    <div class="field" style="margin-top:12px">
        <label>パスワード</label>
        <input class="input {{ $errors->has('password') ? 'is-invalid' : '' }}"
            type="password" name="password">
        @error('password') <p class="error">{{ $message }}</p> @enderror
    </div>

    <label>
    <input type="checkbox" name="remember"> ログイン状態を保持
    </label>

    <div style="margin-top:16px">
        <x-button type="submit" variant="primary">管理者ログインする</x-button>
    </div>
    </form>
</div>
@endsection