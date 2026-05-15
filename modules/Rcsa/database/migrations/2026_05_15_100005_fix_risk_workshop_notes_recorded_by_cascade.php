<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Change risk_workshop_notes.recorded_by from RESTRICT to SET NULL on user deletion.
 *
 * Fresh installs: this migration is a no-op because the original migration
 * (100004) already creates the FK with nullOnDelete().
 *
 * Existing installs: drops the old RESTRICT FK and re-adds it with SET NULL,
 * then makes the column nullable if it isn't already.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            Schema::table('risk_workshop_notes', function (Blueprint $table): void {
                // Make the column nullable in case it isn't already.
                $table->unsignedBigInteger('recorded_by')->nullable()->change();

                // Drop the existing FK regardless of its name so we can re-add it.
                // dropForeign() accepts the column array; Laravel derives the key name.
                $table->dropForeign(['recorded_by']);

                // Re-add with SET NULL behaviour.
                $table->foreign('recorded_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            Schema::table('risk_workshop_notes', function (Blueprint $table): void {
                $table->dropForeign(['recorded_by']);

                $table->foreign('recorded_by')
                    ->references('id')
                    ->on('users');

                // Restore NOT NULL (matches original schema).
                $table->unsignedBigInteger('recorded_by')->nullable(false)->change();
            });
        });
    }
};
