<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('control_tests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('control_id');
            $table->foreign('control_id')->references('id')->on('controls')->cascadeOnDelete();
            $table->unsignedBigInteger('tested_by');
            $table->foreign('tested_by')->references('id')->on('users');
            $table->timestamp('tested_at');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->integer('sample_size')->nullable();
            $table->integer('population_size')->nullable();
            $table->decimal('confidence_level', 4, 3)->nullable();
            $table->string('outcome', 50);
            $table->string('evidence_url', 500)->nullable();
            $table->text('findings')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'control_id']);
            $table->index(['tenant_id', 'outcome']);
            $table->index('tested_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_tests');
    }
};
