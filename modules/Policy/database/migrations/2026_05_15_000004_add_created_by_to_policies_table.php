<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('policies', function (Blueprint $table): void {
            if (! Schema::hasColumn('policies', 'created_by')) {
                $table->unsignedBigInteger('created_by')
                    ->nullable()
                    ->after('body');

                $table->index('created_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('policies', function (Blueprint $table): void {
            if (Schema::hasColumn('policies', 'created_by')) {
                $table->dropIndex(['created_by']);
                $table->dropColumn('created_by');
            }
        });
    }
};
