<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_assessment_cycles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('reference', 50)->unique();
            $table->string('name', 200);
            $table->string('lob', 100);
            $table->string('state', 50)->default('planning');
            $table->unsignedInteger('cycle_year');
            $table->unsignedSmallInteger('cycle_quarter')->nullable();
            $table->date('started_at')->nullable();
            $table->date('closed_at')->nullable();
            $table->date('sla_due_date')->nullable();
            $table->string('methodology', 20)->default('3x3');
            $table->text('summary')->nullable();
            $table->unsignedBigInteger('lead_assessor_id')->nullable();
            $table->foreign('lead_assessor_id')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'state']);
            $table->index(['tenant_id', 'cycle_year']);
            $table->index(['tenant_id', 'lob']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_assessment_cycles');
    }
};
