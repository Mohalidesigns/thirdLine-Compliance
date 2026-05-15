<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table): void {
            if (! Schema::hasColumn('issues', 'linked_obligation_id')) {
                $table->unsignedBigInteger('linked_obligation_id')->nullable()->after('linked_risk_id');
                $table->index('linked_obligation_id');
            }

            if (! Schema::hasColumn('issues', 'linked_policy_id')) {
                $table->unsignedBigInteger('linked_policy_id')->nullable()->after('linked_obligation_id');
                $table->index('linked_policy_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table): void {
            if (Schema::hasColumn('issues', 'linked_policy_id')) {
                $table->dropIndex(['linked_policy_id']);
                $table->dropColumn('linked_policy_id');
            }

            if (Schema::hasColumn('issues', 'linked_obligation_id')) {
                $table->dropIndex(['linked_obligation_id']);
                $table->dropColumn('linked_obligation_id');
            }
        });
    }
};
