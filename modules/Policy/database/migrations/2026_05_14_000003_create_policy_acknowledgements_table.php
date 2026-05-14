<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_acknowledgements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('policy_id')->constrained('policies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('acknowledged_at');
            $table->unsignedInteger('policy_version');

            $table->unique(['policy_id', 'user_id', 'policy_version']);
            $table->index(['tenant_id', 'policy_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_acknowledgements');
    }
};
