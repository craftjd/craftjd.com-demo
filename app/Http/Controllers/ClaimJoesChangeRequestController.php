<?php

namespace App\Http\Controllers;

use App\Services\JoesFulfillmentService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ClaimJoesChangeRequestController extends Controller
{
    public function __invoke(Request $request, int $mainRequestId, JoesFulfillmentService $fulfillments): View
    {
        $expires = (int) $request->query('expires', '0');
        $signature = (string) $request->query('signature', '');

        if (! $fulfillments->validateClaimSignature($mainRequestId, $expires, $signature)) {
            abort(403, 'Invalid or expired claim link.');
        }

        if ($mainRequestId < 1) {
            abort(404);
        }

        try {
            $claim = $fulfillments->claim($mainRequestId);
        } catch (RuntimeException $exception) {
            abort(409, $exception->getMessage());
        }

        return view('pages.joes-claim-confirmed', [
            'mainRequestId' => $mainRequestId,
            'claim' => $claim,
        ]);
    }
}
