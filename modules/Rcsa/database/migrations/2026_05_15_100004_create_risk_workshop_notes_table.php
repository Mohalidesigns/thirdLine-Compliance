<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_workshop_notes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('cycle_id');
            $table->foreign('cycle_id')->references('id')->on('risk_assessment_cycles')->cascadeOnDelete();
            $table->unsignedBigInteger('recorded_by');
            $table->foreign('recorded_by')->references('id')->on('users');
            $table->timestamp('recorded_at');

            $table->json('attendees')->nullable();

            $table->text('notes');

            $table->index('cycle_id');
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_workshop_notes');
    }
};
