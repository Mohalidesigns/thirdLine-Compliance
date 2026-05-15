<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('code', 20)->unique();
            $table->string('title', 500);
            $table->text('description');
            $table->string('category', 50); // cyber|data_breach|conduct|financial_crime|operational|customer_protection|other
            $table->string('severity', 20);  // critical|high|medium|low
            $table->string('basel_category', 50)->nullable(); // internal_fraud|external_fraud|...
            $table->string('status', 30)->default('detected'); // detected|triaged|investigating|remediation|resolved|closed

            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('detected_at');

            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reporter_external_source', 200)->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->decimal('financial_impact', 18, 2)->nullable();
            $table->char('currency', 3)->nullable();

            $table->boolean('is_data_breach')->default(false);
            $table->boolean('is_cyber_incident')->default(false);
            $table->boolean('affects_customers')->default(false);
            $table->unsignedInteger('affected_customer_count')->nullable();

            $table->text('root_cause')->nullable();
            $table->text('lessons_learned')->nullable();

            // Soft FKs — no DB-level foreign key, just indexed
            $table->unsignedBigInteger('linked_risk_id')->nullable()->index();
            $table->unsignedBigInteger('linked_control_id')->nullable()->index();
            $table->unsignedBigInteger('linked_policy_id')->nullable()->index();

            $table->timestamp('closed_at')->nullable()->index();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();

            // Denormalized count for closure guard — updated by observer
            $table->unsignedInteger('closure_evidence_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'severity']);
            $table->index(['tenant_id', 'category']);
            $table->index('detected_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
