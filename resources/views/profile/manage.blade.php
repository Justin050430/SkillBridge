@extends('layouts.admin')

@section('title', 'My Profile')

@section('header')
<x-ui.page-header
    title="My Profile"
    subtitle="View and manage your account." />
@endsection

@section('content')

@include('profile.partials.profile-content')

@endsection
