@extends('layouts.base')

@section('title', 'Claim confirmed — Joe\'s Cafe')
@section('meta_description', 'Change request claim confirmed on Joe\'s Cafe.')

@section('content')
    <div class="mx-auto max-w-xl px-4 py-16">
        <p class="kicker-active">Claim confirmed</p>
        <h1 class="type-title mt-3 m-0">Request #{{ $mainRequestId }} is claimed on Joe's</h1>
        <p class="type-body mt-4 m-0">
            Claim recorded at {{ $claim->claimed_at->timezone(config('app.timezone'))->format('M j, Y g:i A T') }}.
            @if ($claim->claim_deploy_git_sha)
                Git SHA at claim: <code>{{ substr($claim->claim_deploy_git_sha, 0, 7) }}</code>.
            @endif
        </p>
        <ol class="type-body mt-8 m-0 grid list-decimal gap-3 ps-5">
            <li>Edit Joe's Cafe for this request.</li>
            <li>
                <strong>Production:</strong> on Joe's server run
                <code>REQUEST_PUBLIC_ID={{ $publicId }} ./bin/internal/deploy.sh</code>,
                then <code>task claim:fulfill PUBLIC_ID={{ $publicId }} SUMMARY="summary of changes"</code>.
            </li>
            <li>
                <strong>Local only:</strong>
                <code>task dev:claim:deploy PUBLIC_ID={{ $publicId }}</code>,
                then <code>task dev:claim:fulfill PUBLIC_ID={{ $publicId }} SUMMARY="summary of changes"</code>.
            </li>
            <li>
                Wrong request?
                <code>task claim:abandon PUBLIC_ID={{ $publicId }}</code> (production main site)
                or <code>task dev:claim:abandon PUBLIC_ID={{ $publicId }}</code> (local).
            </li>
        </ol>
    </div>
@endsection
