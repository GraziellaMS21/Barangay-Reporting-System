@extends('layouts.app')
@section('title', 'Staff Login')
@section('page-wrap-class', 'login-wrap')

@section('hero')
  <section class="hero hero-compact">
    <div class="hero-inner">
      <h1>Staff sign in</h1>
      <p>Access the report queue to review resident submissions and share status updates.</p>
    </div>
  </section>
@endsection

@section('content')
<section class="card login-card">
  <h2><i class="fa-solid fa-user-shield" aria-hidden="true"></i> Welcome back</h2>
  <p class="login-intro">Sign in with your barangay staff account.</p>

  @if($errors->any())
    <div role="alert" class="form-errors">
      {{ $errors->first() }}
    </div>
  @endif

  <form method="POST" action="{{ route('login') }}" class="login-form">
    @csrf
    <div>
      <label for="email">Email address</label>
      <input id="email" name="email" type="email" value="{{ old('email') }}"
        autocomplete="username" required autofocus>
      @error('email')
        <p class="field-hint">{{ $message }}</p>
      @enderror
    </div>

    <div>
      <label for="password">Password</label>
      <input id="password" name="password" type="password" autocomplete="current-password" required>
    </div>

    <label class="remember">
      <input name="remember" type="checkbox" value="1" @checked(old('remember'))>
      Remember me
    </label>

    <button type="submit" class="btn btn-gold">
      <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Sign in
    </button>
  </form>

  <div class="login-signup-link">
    <p>Partner agency representative?</p>
    <a href="{{ route('agency.register.show') }}">
      <i class="fa-solid fa-building-shield" aria-hidden="true"></i> Register for agency access
    </a>
  </div>
</section>
@endsection
