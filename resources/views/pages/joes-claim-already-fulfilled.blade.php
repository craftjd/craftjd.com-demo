@extends('layouts.base')

@section('title', 'Request already fulfilled — Joe\'s Cafe')
@section('meta_description', 'This change request was already fulfilled on the main site.')

@section('content')
    <div class="mx-auto max-w-xl px-4 py-16">
        <p class="kicker-active">Already fulfilled</p>
        <h1 class="type-title mt-3 m-0">Request #{{ $mainRequestId }} is complete</h1>
        <p class="type-body mt-4 m-0">
            The main site marked this request fulfilled
            @if ($preview->completedAt)
                at {{ $preview->completedAt->timezone(config('app.timezone'))->format('M j, Y g:i A T') }}.
            @else
                .
            @endif
        </p>
        @if ($preview->fulfillmentSummary)
            <p class="type-body mt-4 m-0">
                Summary: {{ $preview->fulfillmentSummary }}
            </p>
        @endif
        <p class="type-body mt-8 m-0">
            No further action is needed on Joe's for this request.
        </p>
    </div>
@endsection
