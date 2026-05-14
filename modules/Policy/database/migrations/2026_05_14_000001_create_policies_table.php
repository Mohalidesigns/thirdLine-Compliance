<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->string('reference', 50)->unique();
            $table->string('title', 500);
            $table->string('category', 50);
            $table->string('owner_team', 200);
            $table->unsignedInteger('version')->default(1);
            $table->string('state', 50)->default('draft');
            $table->date('effective_date')->nullable();
            $table->date('next_review_date')->nullable();
            $table->text('summary')->nullable();
            $table->text('body')->nullable();
            $table->string('published_pdf_path', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'state']);
            $table->index(['tenant_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policies');
    }
};
