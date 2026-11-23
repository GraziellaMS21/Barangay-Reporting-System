@extends('layouts.admin')

@section('title', 'Update Account Password')
@section('page-heading', 'Update Account Password')

@section('content')
<div class="max-w-lg mx-auto py-6">
    @if($user?->must_change_password)
        <div class="mb-6 rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-900 flex items-start gap-3">
            <i class="fa-solid fa-shield-halved text-amber-600 mt-0.5"></i>
            <div class="text-sm">
                <strong class="block font-bold">Security Notice: You must set a permanent password before continuing</strong>
                <span class="text-amber-800">Your account is using a temporary password. Choose a new password of at least 10 characters.</span>
            </div>
        </div>
    @endif

    <x-ui.card title="Change Password">
        <form method="POST" action="{{ route('account.password.update') }}" class="space-y-4">
            @csrf

            <x-ui.form-field label="Current Password" name="current_password" hint="Leave blank only if resetting from an administrator prompt.">
                <input id="current_password" name="current_password" type="password" autocomplete="current-password"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
            </x-ui.form-field>

            <x-ui.form-field label="New Password" name="password" :required="true" hint="Minimum 10 characters; must differ from your email address.">
                <input id="password" name="password" type="password" required minlength="10" autocomplete="new-password"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
            </x-ui.form-field>

            <x-ui.form-field label="Confirm New Password" name="password_confirmation" :required="true">
                <input id="password_confirmation" name="password_confirmation" type="password" required minlength="10" autocomplete="new-password"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
            </x-ui.form-field>

            <div class="pt-2">
                <x-ui.button type="submit" variant="primary" class="w-full">
                    <i class="fa-solid fa-key"></i> Update Password &amp; Proceed
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
@endsection
