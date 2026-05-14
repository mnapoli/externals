@extends('layout')

@section('content')

    <h1 class="text-xl mb-8">Most interesting threads of the last month</h1>

    @include('threads.thread-list')

@endsection
