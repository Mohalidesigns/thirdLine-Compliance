<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_runs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();

            $table->foreignId('return_definition_id')
                ->constrained('return_definitions')
                ->cascadeOnDelete();

            // e.g. '2026-04' or 'Q2-2026' or '2026'
            $table->string('period_label', 20);
            $table->date('period_start');
            $table->date('period_end');
            $table->dateTime('due_at');

            // scheduled|in_progress|submitted_pending_ack|acknowledged|late|rejected
            $table->string('status', 30)->default('scheduled');

            // Maker / checker / approver separation of duties
            $table->foreignId('maker_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('checker_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('payload_path', 500)->nullable(); // generated file path

            $table->string('submission_reference', 200)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('acknowledgement_path', 500)->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // A definition can only have one run per period
            $table->unique(['return_definition_id', 'period_label']);

            // Query indexes
            $table->index(['tenant_id', 'status', 'due_at']);
            $table->index(['tenant_id', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_runs');
    }
};
