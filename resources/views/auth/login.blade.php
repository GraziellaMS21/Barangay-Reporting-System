@extends('layouts.app')
@section('title', 'Staff Login')

@section('content')
<div class="mx-auto max-w-md rounded bg-white p-6 shadow">
  <h1 class="mb-2 text-2xl font-bold">Staff sign in</h1>
  <p class="mb-6 text-sm text-gray-600">Sign in to review and manage barangay reports.</p>

  @if($errors->any())
    <div role="alert" class="mb-4 rounded border border-red-300 bg-red-100 p-3 text-sm text-red-800">
      {{ $errors->first() }}
    </div>
  @endif

  <form method="POST" action="{{ route('login') }}" class="space-y-4">
    @csrf

    <div>
      <label for="email" class="mb-1 block font-medium">Email address</label>
      <input id="email" name="email" type="email" value="{{ old('email') }}"
        autocomplete="username" required autofocus
        class="w-full rounded border p-2 @error('email') border-red-500 @enderror">
      @error('email')
        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
      @enderror
    </div>

    <div>
      <label for="password" class="mb-1 block font-medium">Password</label>
      <input id="password" name="password" type="password" autocomplete="current-password"
        required class="w-full rounded border p-2">
    </div>

    <label class="flex items-center gap-2 text-sm">
      <input name="remember" type="checkbox" value="1" @checked(old('remember'))>
      Remember me
    </label>

    <button type="submit" class="w-full rounded bg-green-700 px-5 py-2 font-medium text-white hover:bg-green-800">
      Sign in
    </button>
  </form>
</div>
@endsection
