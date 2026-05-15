<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_loss_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('incident_id')->unique()->constrained('incidents')->cascadeOnDelete();
            $table->string('basel_category', 50);
            $table->decimal('gross_loss', 18, 2);
            $table->decimal('recovery_amount', 18, 2)->default(0);
            $table->char('net_loss_currency', 3)->default('NGN');
            $table->date('event_date');
            $table->date('recognized_date');
            $table->timestamps();

            $table->index(['tenant_id', 'basel_category']);
            $table->index('event_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_loss_events');
    }
};
