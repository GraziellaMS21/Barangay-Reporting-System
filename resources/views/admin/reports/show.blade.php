@extends('layouts.admin')
@section('title', 'Review ' . $report->tracking_code)
@section('page-heading', 'Review report')

@section('content')
  @include('admin.reports.review-workflow')
  {{-- Shared "Ask Reporter for More Info" chat modal (triggered by [data-chat-open]). --}}
  @include('admin.reports.partials.info-thread-modal')
@endsection
