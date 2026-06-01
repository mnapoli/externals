@extends('layout')

@section('content')

    <p class="mb-4">It seems there was an error while authenticating:</p>

    <blockquote>{{ $error }}</blockquote>

@endsection
