<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('incident_id')->constrained('incidents')->cascadeOnDelete();
            $table->string('regulator', 30); // cbn_cyber|cbn_general|ndpc|nfiu|sec|others
            $table->timestamp('deadline_at');
            $table->timestamp('notified_at')->nullable();
            $table->string('notification_reference', 200)->nullable();
            $table->string('status', 20)->default('pending'); // pending|submitted|acknowledged|overdue
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'deadline_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_notifications');
    }
};
