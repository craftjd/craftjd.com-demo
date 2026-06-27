<?php

namespace App\Data;

use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Transient preview fetched from the main site — never persisted on Joe's.
 */
readonly class JoesChangeRequestPreview
{
    public function __construct(
        public string $publicId,
        public int $number,
        public string $clientName,
        public string $clientEmail,
        public string $requestBody,
        public Carbon $submittedAt,
        public ?Carbon $completedAt = null,
        public ?string $fulfillmentSummary = null,
    ) {}

    public function isCompleted(): bool
    {
        return $this->completedAt !== null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $publicId = (string) ($payload['public_id'] ?? '');
        $number = $payload['number'] ?? $payload['id'] ?? null;
        $submittedAt = $payload['submitted_at'] ?? null;

        if ($publicId === '' || (! is_int($number) && ! (is_string($number) && ctype_digit((string) $number)))) {
            throw new RuntimeException('Main site response is missing public_id or number.');
        }

        if (! is_string($submittedAt) || $submittedAt === '') {
            throw new RuntimeException('Main site response is missing submitted_at.');
        }

        try {
            $submitted = Carbon::parse($submittedAt);
        } catch (\Throwable) {
            throw new RuntimeException('Main site submitted_at is not a valid timestamp.');
        }

        $completedAt = null;
        $completedAtRaw = $payload['completed_at'] ?? null;

        if (is_string($completedAtRaw) && $completedAtRaw !== '') {
            try {
                $completedAt = Carbon::parse($completedAtRaw);
            } catch (\Throwable) {
                throw new RuntimeException('Main site completed_at is not a valid timestamp.');
            }
        }

        $fulfillmentSummary = ($payload['fulfillment_summary'] ?? null);
        $fulfillmentSummary = is_string($fulfillmentSummary) && trim($fulfillmentSummary) !== ''
            ? trim($fulfillmentSummary)
            : null;

        return new self(
            publicId: $publicId,
            number: (int) $number,
            clientName: trim((string) ($payload['client_name'] ?? $payload['name'] ?? '')),
            clientEmail: trim((string) ($payload['client_email'] ?? $payload['email'] ?? '')),
            requestBody: trim((string) ($payload['request'] ?? $payload['body'] ?? $payload['message'] ?? '')),
            submittedAt: $submitted,
            completedAt: $completedAt,
            fulfillmentSummary: $fulfillmentSummary,
        );
    }
}
