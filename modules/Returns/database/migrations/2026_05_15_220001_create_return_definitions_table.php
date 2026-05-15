<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_definitions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();

            // Unique code per tenant (e.g. 'CBN-AML-MONTHLY')
            $table->string('code', 60);
            $table->string('title', 500);
            $table->text('description')->nullable();

            // Legal references
            $table->string('acts', 500)->nullable();
            $table->string('legal_basis', 200)->nullable();

            // Categorical fields
            $table->string('regulator', 20); // cbn|ndic|nfiu|sec|ndpc|firs|pencom|scuml|other
            $table->string('submission_channel', 30); // portal|api|sftp|email|goaml_xml|firs_tax_pro_max|cbn_efass|manual
            $table->string('file_format', 20)->default('pdf'); // xml|xlsx|json|pdf|csv|txt|other
            $table->string('frequency', 20); // daily|weekly|monthly|quarterly|half_year|annual|event_driven|ad_hoc

            $table->string('responsible_unit', 200)->nullable();
            $table->json('approval_matrix')->nullable(); // { maker_role, checker_role, approver_role }

            $table->boolean('evidence_required')->default(true);
            $table->boolean('active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Unique code per tenant
            $table->unique(['tenant_id', 'code']);

            // Query indexes
            $table->index(['tenant_id', 'regulator', 'frequency']);
            $table->index(['tenant_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_definitions');
    }
};
