<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('code', 50);
            $table->string('title', 300);
            $table->text('description')->nullable();
            $table->string('category', 50); // aml, sanctions, ndpa, abac, conduct, ethics, esg, cyber, customer_protection, general
            $table->boolean('is_mandatory')->default(false);
            $table->json('target_roles')->nullable(); // empty = all users
            $table->unsignedSmallInteger('sla_days')->default(30);
            $table->string('source', 20)->default('native'); // native, scorm, xapi, external
            $table->string('source_url')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->index(['tenant_id', 'category']);
            $table->index(['tenant_id', 'is_mandatory']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainings');
    }
};
