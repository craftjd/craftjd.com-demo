@extends('layouts.base')

@section('title', 'Page not found')

@section('content')
    <section class="section">
        <div class="container max-w-lg text-center">
            <p class="text-sm font-semibold uppercase tracking-wider text-blue-600">404</p>
            <h1 class="mt-2 text-4xl font-bold text-gray-900">Page not found</h1>
            <p class="mt-4 text-gray-600">
                The page you're looking for doesn't exist or may have been moved.
            </p>
            <a href="{{ url('/') }}" class="btn btn-primary mt-8">Back to home</a>
        </div>
    </section>
@endsection
