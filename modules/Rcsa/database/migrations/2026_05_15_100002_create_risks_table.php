<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('cycle_id');
            $table->foreign('cycle_id')->references('id')->on('risk_assessment_cycles')->cascadeOnDelete();
            $table->string('reference', 50)->unique();
            $table->string('title', 300);
            $table->text('description');
            $table->string('category', 50);
            $table->string('risk_owner', 200)->nullable();
            $table->unsignedSmallInteger('inherent_likelihood')->nullable();
            $table->unsignedSmallInteger('inherent_impact')->nullable();
            $table->unsignedSmallInteger('inherent_score')->nullable();
            $table->string('inherent_rating', 20)->nullable();
            $table->unsignedSmallInteger('residual_likelihood')->nullable();
            $table->unsignedSmallInteger('residual_impact')->nullable();
            $table->unsignedSmallInteger('residual_score')->nullable();
            $table->string('residual_rating', 20)->nullable();

            $table->json('linked_obligation_ids')->nullable();

            $table->text('mitigation_summary')->nullable();
            $table->text('accept_basis')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'cycle_id']);
            $table->index(['tenant_id', 'category']);
            $table->index(['tenant_id', 'residual_rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risks');
    }
};
