@extends('layouts.base')

@section('title', 'Records')
@section('meta_description', 'Flat-file data with a Livewire paginated table.')

@section('content')
    <x-page-header
        title="Records"
        description="Structured data lives in storage/app/private/data/. This page reads records.json via a Livewire component."
    />

    <section class="section">
        <div class="container">
            <livewire:records-table />
        </div>
    </section>
@endsection
