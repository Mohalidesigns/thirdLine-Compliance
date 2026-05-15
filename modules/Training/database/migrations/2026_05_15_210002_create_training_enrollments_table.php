<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_enrollments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('enrolled_at');
            $table->timestamp('due_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedSmallInteger('score')->nullable(); // 0–100
            $table->timestamp('exempted_at')->nullable();
            $table->string('exemption_reason')->nullable();
            $table->string('status', 20)->default('enrolled'); // enrolled, in_progress, completed, overdue, exempted
            $table->foreignId('enrolled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // One active enrollment per user per training.
            $table->unique(['training_id', 'user_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'user_id', 'status']);
            $table->index('due_at'); // for the overdue command
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_enrollments');
    }
};
