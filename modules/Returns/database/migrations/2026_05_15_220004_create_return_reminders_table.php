<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_reminders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();

            $table->foreignId('return_run_id')
                ->constrained('return_runs')
                ->cascadeOnDelete();

            $table->dateTime('reminder_at');
            $table->unsignedInteger('days_before_due'); // 30|14|7|1

            $table->timestamp('fired_at')->nullable();

            // email|in_app
            $table->string('channel', 20)->default('in_app');

            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_reminders');
    }
};
