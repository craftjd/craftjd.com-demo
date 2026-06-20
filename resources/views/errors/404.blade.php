@extends('layouts.base')

@section('title', '404 — Not on the menu')
@section('meta_description', 'That page is not here. Try the menu.')

@section('content')
    <section class="error-page">
        <div class="site-wrap error-page__inner">
            <x-joes.logo-mark class="error-page__mark" />

            <p class="error-page__code">404</p>
            <h1 class="error-page__title">Not on the menu.</h1>
            <p class="error-page__lead">
                Whatever you asked for, we don't have it. No back room. No secret URL. You're in the wrong line.
            </p>
            <p class="error-page__aside">
                If you were looking for coffee, food, or directions — those actually exist.
            </p>

            <div class="error-page__actions">
                <a href="{{ url('/') }}" class="btn btn-primary">Back to the counter</a>
                <a href="{{ url('/#coffee') }}" class="btn btn-secondary">See the menu</a>
            </div>
        </div>
    </section>
@endsection
