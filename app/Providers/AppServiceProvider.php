<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\AttestationCampaignPolicy;
use App\Policies\AuditEventPolicy;
use App\Policies\CertificationPolicy;
use App\Policies\ControlPolicy;
use App\Policies\ControlTestPolicy;
use App\Policies\IssuePolicy;
use App\Policies\PolicyPolicy;
use App\Policies\RiskAssessmentCyclePolicy;
use App\Policies\RiskPolicy;
use App\Policies\TrainingEnrollmentPolicy;
use App\Policies\TrainingPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Modules\Audit\Models\AuditEvent;
use Modules\Controls\Models\Control;
use Modules\Controls\Models\ControlTest;
use Modules\Controls\Models\Issue;
use Modules\Policy\Models\Policy;
use Modules\Rcsa\Models\Risk;
use Modules\Rcsa\Models\RiskAssessmentCycle;
use Modules\Training\Models\AttestationCampaign;
use Modules\Training\Models\Certification;
use Modules\Training\Models\Training;
use Modules\Training\Models\TrainingEnrollment;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // super_admin bypasses every policy check.
        // Returning null (not false) for other users lets normal policy logic run.
        Gate::before(function (User $user) {
            return $user->hasRole('super_admin') ? true : null;
        });

        // Register domain model → policy class mappings.
        Gate::policy(Policy::class, PolicyPolicy::class);
        Gate::policy(RiskAssessmentCycle::class, RiskAssessmentCyclePolicy::class);
        Gate::policy(Risk::class, RiskPolicy::class);
        Gate::policy(Control::class, ControlPolicy::class);
        Gate::policy(ControlTest::class, ControlTestPolicy::class);
        Gate::policy(Issue::class, IssuePolicy::class);
        Gate::policy(AuditEvent::class, AuditEventPolicy::class);
        Gate::policy(Training::class, TrainingPolicy::class);
        Gate::policy(TrainingEnrollment::class, TrainingEnrollmentPolicy::class);
        Gate::policy(AttestationCampaign::class, AttestationCampaignPolicy::class);
        Gate::policy(Certification::class, CertificationPolicy::class);
    }
}
