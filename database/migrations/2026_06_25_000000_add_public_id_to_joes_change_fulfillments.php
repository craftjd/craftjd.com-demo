<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('joes_change_fulfillments')) {
            return;
        }

        Schema::table('joes_change_fulfillments', function (Blueprint $table) {
            if (! Schema::hasColumn('joes_change_fulfillments', 'main_request_public_id')) {
                $table->uuid('main_request_public_id')->nullable()->after('id');
                $table->unique('main_request_public_id');
            }

            if (! Schema::hasColumn('joes_change_fulfillments', 'main_request_submitted_at')) {
                $table->timestamp('main_request_submitted_at')->nullable()->after('main_request_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('joes_change_fulfillments')) {
            return;
        }

        Schema::table('joes_change_fulfillments', function (Blueprint $table) {
            if (Schema::hasColumn('joes_change_fulfillments', 'main_request_submitted_at')) {
                $table->dropColumn('main_request_submitted_at');
            }

            if (Schema::hasColumn('joes_change_fulfillments', 'main_request_public_id')) {
                $table->dropUnique(['main_request_public_id']);
                $table->dropColumn('main_request_public_id');
            }
        });
    }
};
