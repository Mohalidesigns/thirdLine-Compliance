<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_approvals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();

            $table->foreignId('return_run_id')
                ->constrained('return_runs')
                ->cascadeOnDelete();

            // maker_submit|checker_review|approver_sign_off
            $table->string('step', 30);

            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();

            // submitted|approved|rejected
            $table->string('decision', 20);
            $table->text('notes')->nullable();
            $table->timestamp('acted_at');

            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'return_run_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_approvals');
    }
};
