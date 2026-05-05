@extends('layouts.base')

@section('title', $title)
@section('meta_description', $metaDescription)

@section('content')
    <x-page-header :title="$title" :description="$metaDescription" />

    <article class="section">
        <div class="container max-w-3xl">
            <div class="prose">
                {!! $html !!}
            </div>
        </div>
    </article>
@endsection
