<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sample_change_fulfillments') && ! Schema::hasTable('joes_change_fulfillments')) {
            Schema::rename('sample_change_fulfillments', 'joes_change_fulfillments');
        }

        if (! Schema::hasTable('joes_change_fulfillments')) {
            return;
        }

        Schema::table('joes_change_fulfillments', function (Blueprint $table) {
            if (! Schema::hasColumn('joes_change_fulfillments', 'deploy_git_sha')) {
                $table->string('deploy_git_sha', 40)->nullable()->after('claim_deploy_git_sha');
            }

            if (! Schema::hasColumn('joes_change_fulfillments', 'deploy_deployed_at')) {
                $table->timestamp('deploy_deployed_at')->nullable()->after('deploy_git_sha');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('joes_change_fulfillments')) {
            return;
        }

        Schema::table('joes_change_fulfillments', function (Blueprint $table) {
            if (Schema::hasColumn('joes_change_fulfillments', 'deploy_deployed_at')) {
                $table->dropColumn('deploy_deployed_at');
            }

            if (Schema::hasColumn('joes_change_fulfillments', 'deploy_git_sha')) {
                $table->dropColumn('deploy_git_sha');
            }
        });

        if (Schema::hasTable('joes_change_fulfillments') && ! Schema::hasTable('sample_change_fulfillments')) {
            Schema::rename('joes_change_fulfillments', 'sample_change_fulfillments');
        }
    }
};
