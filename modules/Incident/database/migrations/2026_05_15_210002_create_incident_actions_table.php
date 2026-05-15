<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_actions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('incident_id')->constrained('incidents')->cascadeOnDelete();
            $table->string('type', 20); // corrective|preventive
            $table->string('title', 500);
            $table->text('description');
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_at');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('evidence_notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'incident_id']);
            $table->index(['owner_user_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_actions');
    }
};
