<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('controls', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('reference', 50)->unique();
            $table->string('title', 300);
            $table->text('description')->nullable();
            $table->string('control_type', 50);
            $table->string('nature', 50);
            $table->string('frequency', 50);
            $table->string('owner_team', 200);

            $table->json('linked_obligation_ids')->nullable();
            $table->json('linked_risk_ids')->nullable();

            $table->string('status', 50)->default('active');
            $table->timestamp('last_tested_at')->nullable();
            $table->date('next_test_due')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'frequency']);
            $table->index(['tenant_id', 'next_test_due']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('controls');
    }
};
