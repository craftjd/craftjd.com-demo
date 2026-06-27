<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JoesChangeFulfillment extends Model
{
    protected $fillable = [
        'main_request_public_id',
        'main_request_id',
        'main_request_submitted_at',
        'claimed_at',
        'claim_deploy_git_sha',
        'deploy_git_sha',
        'deploy_deployed_at',
        'fulfilled_at',
        'fulfill_deploy_git_sha',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'claimed_at' => 'datetime',
            'main_request_submitted_at' => 'datetime',
            'deploy_deployed_at' => 'datetime',
            'fulfilled_at' => 'datetime',
            'main_request_id' => 'integer',
        ];
    }

    public function isPending(): bool
    {
        return $this->fulfilled_at === null;
    }

    public function pipelineStep(): string
    {
        if ($this->fulfilled_at !== null) {
            return 'done';
        }

        if ($this->deploy_deployed_at !== null) {
            return 'deployed';
        }

        return 'claimed';
    }

    public function pipelineAt(): \Illuminate\Support\Carbon
    {
        return $this->fulfilled_at
            ?? $this->deploy_deployed_at
            ?? $this->claimed_at
            ?? $this->created_at;
    }

    public static function truncatePublicId(string $publicId): string
    {
        return strlen($publicId) > 8 ? substr($publicId, 0, 8).'…' : $publicId;
    }

    public static function pendingForPublicId(string $publicId): ?self
    {
        return self::query()
            ->where('main_request_public_id', $publicId)
            ->whereNull('fulfilled_at')
            ->latest('claimed_at')
            ->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public static function pending()
    {
        return self::query()
            ->whereNull('fulfilled_at')
            ->latest('claimed_at')
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public static function fulfilled()
    {
        return self::query()
            ->whereNotNull('fulfilled_at')
            ->get();
    }
}
