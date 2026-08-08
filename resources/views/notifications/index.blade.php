@extends('layouts.admin')

@section('title', 'Notifications')

@section('header')
<x-ui.page-header
    title="Notifications"
    subtitle="Your activity updates." />
@endsection

@section('content')

@include('notifications._list', ['notifications' => $notifications])

@endsection
