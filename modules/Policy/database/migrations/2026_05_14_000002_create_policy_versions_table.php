<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('policy_id')->constrained('policies')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('state', 50);
            $table->text('body_snapshot')->nullable();
            $table->string('published_pdf_path', 500)->nullable();
            $table->unsignedBigInteger('transitioned_by')->nullable();
            $table->timestamp('transitioned_at');
            $table->text('transition_note')->nullable();

            $table->foreign('transitioned_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['policy_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_versions');
    }
};
