@extends('layouts.admin')
@section('title', 'Review ' . $report->tracking_code)
@section('page-heading', 'Review report')

@section('content')
  @include('admin.reports.review-workflow')
@endsection
