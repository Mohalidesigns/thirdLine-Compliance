<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('risk_appetite_thresholds', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('lob', 100);
            $table->string('category', 50);
            $table->string('acceptable_rating', 20);
            $table->text('breach_action')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['tenant_id', 'lob', 'category']);
            $table->index(['tenant_id', 'lob']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_appetite_thresholds');
    }
};
