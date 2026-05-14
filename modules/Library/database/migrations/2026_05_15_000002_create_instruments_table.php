<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instruments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->string('source_title');
            $table->text('objectives')->nullable();
            $table->date('date_issue')->nullable();
            $table->date('date_commence')->nullable();
            $table->date('date_repeal')->nullable();
            $table->foreignId('regulator_id')->constrained('regulators');
            $table->foreignId('instrument_type_id')->constrained('instrument_types');
            $table->foreignId('nature_id')->constrained('natures');
            $table->foreignId('status_id')->constrained('statuses');
            $table->foreignId('area_of_focus_id')->constrained('areas_of_focus');
            $table->foreignId('risk_rating_id')->constrained('risk_ratings');
            $table->text('risk_rating_explain')->nullable();
            $table->text('commercial_bank_relevance')->nullable();
            $table->text('commercial_bank_compliance_context')->nullable();
            $table->string('applicability', 20)->default('Yes');
            $table->string('link_url')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('instruments')->nullOnDelete();
            $table->index(['tenant_id', 'regulator_id']);
            $table->index(['tenant_id', 'status_id']);
            $table->index(['tenant_id', 'risk_rating_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instruments');
    }
};
