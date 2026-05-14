<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $this->createPostgres();
        } else {
            $this->createSqlite();
        }
    }

    private function createPostgres(): void
    {
        Schema::create('sanctions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->foreignId('regulator_id')->constrained('regulators');
            $table->string('reference')->nullable();
            $table->string('section')->nullable();
            $table->text('offence');
            $table->string('party_name');
            $table->string('party_type', 30)->default('institution');
            $table->decimal('amount_naira', 20, 2)->nullable();
            $table->string('penalty_type', 30)->default('monetary');
            $table->date('effective_date');
            $table->string('source_url')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'regulator_id']);
            $table->index(['tenant_id', 'effective_date']);
        });

        // Add the tsvector generated column after table creation
        DB::statement(<<<'SQL'
            ALTER TABLE sanctions
            ADD COLUMN content tsvector
                GENERATED ALWAYS AS (
                    to_tsvector(
                        'english',
                        offence || ' ' || party_name || ' ' ||
                        COALESCE(reference, '') || ' ' || COALESCE(section, '')
                    )
                ) STORED
        SQL);

        DB::statement('CREATE INDEX idx_sanctions_content_gin ON sanctions USING GIN (content)');

        // Materialized view for penalty exposure aggregation
        DB::statement(<<<'SQL'
            CREATE MATERIALIZED VIEW IF NOT EXISTS vw_penalty_exposure AS
            SELECT
                regulator_id,
                COUNT(*) AS sanction_count,
                SUM(amount_naira) AS total_amount_naira,
                MAX(effective_date) AS latest_date
            FROM sanctions
            WHERE deleted_at IS NULL
            GROUP BY regulator_id
        SQL);

        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS idx_vw_penalty_exposure_regulator ON vw_penalty_exposure (regulator_id)');
    }

    private function createSqlite(): void
    {
        Schema::create('sanctions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->default(1)->index();
            $table->unsignedBigInteger('regulator_id');
            $table->string('reference')->nullable();
            $table->string('section')->nullable();
            $table->text('offence');
            $table->string('party_name');
            $table->string('party_type', 30)->default('institution');
            $table->decimal('amount_naira', 20, 2)->nullable();
            $table->string('penalty_type', 30)->default('monetary');
            $table->date('effective_date');
            $table->string('source_url')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP MATERIALIZED VIEW IF EXISTS vw_penalty_exposure');
        }
        Schema::dropIfExists('sanctions');
    }
};
