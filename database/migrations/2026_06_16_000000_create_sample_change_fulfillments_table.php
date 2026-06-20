<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sample_change_fulfillments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('main_request_id');
            $table->timestamp('claimed_at');
            $table->string('claim_deploy_git_sha', 40)->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->string('fulfill_deploy_git_sha', 40)->nullable();
            $table->text('summary')->nullable();
            $table->timestamps();

            $table->index('main_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sample_change_fulfillments');
    }
};
