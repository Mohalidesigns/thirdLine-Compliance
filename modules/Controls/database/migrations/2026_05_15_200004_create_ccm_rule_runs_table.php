<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ccm_rule_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('rule_name', 100);
            $table->timestamp('run_at');
            $table->string('status', 50);
            $table->decimal('metric_value', 20, 4)->nullable();
            $table->decimal('threshold', 20, 4)->nullable();

            $table->json('details')->nullable();

            $table->unsignedBigInteger('issue_id')->nullable();

            $table->index('rule_name');
            $table->index('run_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ccm_rule_runs');
    }
};
