@extends('layouts.staff')
@section('nav') @endsection {{-- ログイン/登録はナビ不要 --}}

@section('content')
  <x-page-title>会員登録</x-page-title>

  <div class="card">
    <form method="POST" action="{{ route('register') }}">
      @csrf

      <div class="field">
        <label>氏名</label>
        <input name="name" type="text" value="{{ old('name') }}"
        class="input {{ $errors->has('name') ? 'is-invalid' : '' }}">
    @error('name')
  <p class="error">{{ $message }}</p>
    @enderror
  </div>
      <div class="field">
        <label>メールアドレス</label>
        <input name="email" type="email" value="{{ old('email') }}"
        class="input {{ $errors->has('email') ? 'is-invalid' : '' }}">
    @error('email')
      <p class="error">{{ $message }}</p>
    @enderror
  </div>
      <div class="field">
        <label>パスワード</label>
        <input name="password" type="password"
        class="input {{ $errors->has('password') ? 'is-invalid' : '' }}">
    @error('password')
      <p class="error">{{ $message }}</p>
    @enderror
  </div>
      <div class="field">
        <label>パスワード（確認）</label>
        <input name="password_confirmation" type="password">
  </div>
      <div style="margin-top:10px;">
        <x-button variant="primary">登録する</x-button>
    </div>
    </form>
  </div>
@endsection