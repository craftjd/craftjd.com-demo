<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JoesChangeFulfillment extends Model
{
    protected $fillable = [
        'main_request_id',
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
            'deploy_deployed_at' => 'datetime',
            'fulfilled_at' => 'datetime',
            'main_request_id' => 'integer',
        ];
    }

    public function isPending(): bool
    {
        return $this->fulfilled_at === null;
    }

    public static function pendingFor(int $mainRequestId): ?self
    {
        return self::query()
            ->where('main_request_id', $mainRequestId)
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
