<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DeployMetaTest extends TestCase
{
    public function test_changelog_reads_deploy_history(): void
    {
        File::put(storage_path('app/deploy-meta.json'), json_encode([
            'git_sha' => 'abc1234',
            'deployed_at' => '2026-06-19T21:17:30Z',
            'summary' => 'Updated contact section hours for the sample site.',
            'request_id' => 42,
            'deployments' => [
                [
                    'git_sha' => 'abc1234',
                    'deployed_at' => '2026-06-19T21:17:30Z',
                    'summary' => 'Updated contact section hours for the sample site.',
                    'request_id' => 42,
                ],
                [
                    'git_sha' => 'def5678',
                    'deployed_at' => '2026-06-10T14:00:00Z',
                    'summary' => 'Seasonal special wording.',
                    'request_id' => 38,
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        $changelog = app(\App\Services\DeployMeta::class)->changelog();

        $this->assertCount(2, $changelog);
        $this->assertSame('2026-06-19', $changelog[0]['date']);
        $this->assertSame('Updated contact section hours for the sample site.', $changelog[0]['summary']);
        $this->assertSame('Seasonal special wording.', $changelog[1]['summary']);
    }

    public function test_changelog_falls_back_to_commit_message_body(): void
    {
        File::put(storage_path('app/deploy-meta.json'), json_encode([
            'git_sha' => 'abc1234',
            'deployed_at' => '2026-06-19T21:17:30Z',
            'git_commit_message' => "feat: update summer hours\n\nUpdated contact section hours for the sample site.",
        ], JSON_THROW_ON_ERROR));

        $changelog = app(\App\Services\DeployMeta::class)->changelog();

        $this->assertCount(1, $changelog);
        $this->assertSame('Updated contact section hours for the sample site.', $changelog[0]['summary']);
    }

    public function test_changelog_falls_back_to_commit_subject_when_no_body(): void
    {
        File::put(storage_path('app/deploy-meta.json'), json_encode([
            'git_sha' => 'abc1234',
            'deployed_at' => '2026-06-19T21:17:30Z',
            'git_commit_message' => 'chore: deploy sample site',
        ], JSON_THROW_ON_ERROR));

        $changelog = app(\App\Services\DeployMeta::class)->changelog();

        $this->assertCount(1, $changelog);
        $this->assertSame('chore: deploy sample site', $changelog[0]['summary']);
    }
}
