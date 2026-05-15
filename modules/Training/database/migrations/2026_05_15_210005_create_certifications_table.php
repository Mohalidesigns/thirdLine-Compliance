<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 100); // e.g. ACAMS, CFE, CIPP
            $table->string('issuing_body', 200);
            $table->string('certificate_no')->nullable();
            $table->date('issued_at');
            $table->date('expires_at')->nullable();
            $table->string('evidence_path')->nullable(); // path in storage
            $table->string('status', 20)->default('active'); // active, expiring, expired
            $table->timestamps();

            $table->index(['tenant_id', 'user_id']);
            $table->index('expires_at'); // for the expiry scan command
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certifications');
    }
};
