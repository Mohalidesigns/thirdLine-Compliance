<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obligations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('instrument_id')->constrained('instruments')->cascadeOnDelete();
            $table->string('reference', 50)->nullable();
            $table->string('title');
            $table->text('description');
            $table->string('due_basis', 30)->default('recurring');
            $table->string('frequency', 30)->nullable();
            $table->date('next_due_date')->nullable();
            $table->string('responsible_team')->nullable();
            $table->string('status', 30)->default('open');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'instrument_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'next_due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obligations');
    }
};
