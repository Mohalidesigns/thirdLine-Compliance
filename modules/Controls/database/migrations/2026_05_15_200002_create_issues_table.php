<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('reference', 50)->unique();
            $table->string('source_type', 50);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('title', 300);
            $table->text('description')->nullable();
            $table->string('severity', 20);
            $table->string('status', 50)->default('open');
            $table->date('due_date')->nullable();
            $table->string('owner_team', 200)->nullable();
            $table->unsignedBigInteger('linked_control_id')->nullable();
            $table->unsignedBigInteger('linked_risk_id')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'severity']);
            $table->index(['tenant_id', 'source_type']);
            $table->index('linked_control_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
