<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_evidence', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('incident_id')->constrained('incidents')->cascadeOnDelete();
            $table->string('type', 30); // document|screenshot|email|log|witness_statement|other
            $table->string('title', 500);
            $table->text('description')->nullable();
            $table->string('file_path', 1000)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable(); // no updated_at — immutable once created

            $table->index(['tenant_id', 'incident_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_evidence');
    }
};
