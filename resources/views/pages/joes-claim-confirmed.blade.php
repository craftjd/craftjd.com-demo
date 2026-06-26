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
            <li>Deploy with <code>REQUEST_ID={{ $mainRequestId }} ./bin/deploy</code>.</li>
            <li>Verify the live site, then run <code>./bin/fulfill {{ $mainRequestId }} "summary of changes"</code>.</li>
        </ol>
    </div>
@endsection
