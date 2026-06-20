<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SampleChangeFulfillment extends Model
{
    protected $fillable = [
        'main_request_id',
        'claimed_at',
        'claim_deploy_git_sha',
        'fulfilled_at',
        'fulfill_deploy_git_sha',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'claimed_at' => 'datetime',
            'fulfilled_at' => 'datetime',
            'main_request_id' => 'integer',
        ];
    }

    public function isPending(): bool
    {
        return $this->fulfilled_at === null;
    }

    public static function pending(): ?self
    {
        return self::query()
            ->whereNull('fulfilled_at')
            ->latest('claimed_at')
            ->first();
    }
}
