<?php

namespace App\Http\Controllers;

use App\Exceptions\MainSiteRequestAlreadyCompleted;
use App\Services\JoesFulfillmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use RuntimeException;

class ClaimJoesChangeRequestController extends Controller
{
    public function __invoke(Request $request, string $publicId, JoesFulfillmentService $fulfillments): View
    {
        $number = (int) $request->query('number', '0');
        $submittedAt = (int) $request->query('submitted_at', '0');
        $expires = (int) $request->query('expires', '0');
        $signature = (string) $request->query('signature', '');

        if (! $fulfillments->validateClaimSignature($publicId, $submittedAt, $expires, $signature)) {
            abort(403, 'Invalid or expired claim link.');
        }

        if ($number < 1 || $submittedAt < 1) {
            abort(422, 'Claim link is missing request number or submitted_at.');
        }

        try {
            $claim = $fulfillments->claimFromSignedLink(
                $publicId,
                $number,
                Carbon::createFromTimestampUTC($submittedAt),
            );
        } catch (MainSiteRequestAlreadyCompleted $exception) {
            $preview = $exception->preview;

            return view('pages.joes-claim-already-fulfilled', [
                'publicId' => $publicId,
                'mainRequestId' => $preview->number,
                'preview' => $preview,
            ]);
        } catch (RuntimeException $exception) {
            abort(409, $exception->getMessage());
        }

        return view('pages.joes-claim-confirmed', [
            'publicId' => $publicId,
            'mainRequestId' => $number,
            'claim' => $claim,
        ]);
    }
}
