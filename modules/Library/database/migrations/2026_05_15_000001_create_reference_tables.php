<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regulators', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('country', 3)->default('NGA');
            $table->string('website_url')->nullable();
            $table->timestamps();
        });

        Schema::create('instrument_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        Schema::create('areas_of_focus', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        Schema::create('natures', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        Schema::create('statuses', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        Schema::create('risk_ratings', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('color', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('risk_ratings');
        Schema::dropIfExists('statuses');
        Schema::dropIfExists('natures');
        Schema::dropIfExists('areas_of_focus');
        Schema::dropIfExists('instrument_types');
        Schema::dropIfExists('regulators');
    }
};
