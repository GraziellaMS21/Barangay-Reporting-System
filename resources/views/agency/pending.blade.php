@extends('layouts.app')

@section('title', 'Registration Pending Review')

@section('content')
<div class="py-16 px-4 sm:px-6 lg:px-8 max-w-lg mx-auto text-center">
  <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-amber-100 text-amber-600 mb-6 shadow-inner">
    <i class="fa-solid fa-clock text-3xl"></i>
  </div>

  <h1 class="text-2xl font-bold tracking-tight" style="color: #ffffff;">Access Request Submitted</h1>
  <p class="text-sm mt-3 leading-relaxed" style="color: #dbe5ff;">
    Thank you for registering. Your agency account request is currently <strong style="color: #ffffff;">pending review and verification</strong> by the Barangay Secretary.
  </p>

  <div class="mt-8 p-6 bg-white rounded-2xl border border-slate-200 shadow-sm text-left space-y-4">
    <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">What happens next?</h2>
    <ul class="text-xs text-slate-600 space-y-2.5">
      <li class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-blue-500 mt-0.5"></i>
        <span>An email verification link has been sent to your registered email address (please click the link if you haven't yet).</span>
      </li>
      <li class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-blue-500 mt-0.5"></i>
        <span>The Barangay Secretary will verify your uploaded official ID or endorsement letter.</span>
      </li>
      <li class="flex items-start gap-2.5">
        <i class="fa-solid fa-circle-check text-blue-500 mt-0.5"></i>
        <span>Once approved, you will receive an official approval notification and full access to the Agency Portal.</span>
      </li>
    </ul>
  </div>

  <div class="mt-8 flex justify-center gap-4">
    <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 shadow-sm">
      <i class="fa-solid fa-arrow-left mr-2"></i> Return to Login
    </a>
  </div>
</div>
@endsection
