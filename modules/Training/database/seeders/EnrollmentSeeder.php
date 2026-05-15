<?php

declare(strict_types=1);

namespace Modules\Training\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Training\Models\TrainingEnrollment;
use Modules\Training\Services\TrainingService;

class EnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(TrainingService::class);

        $demoEmails = [
            'test@example.com',
            'compliance@example.com',
            'riskowner@example.com',
            'policyowner@example.com',
            'tester@example.com',
            'auditor@example.com',
        ];

        $users = User::whereIn('email', $demoEmails)->get();
        $admin = User::where('email', 'test@example.com')->first();

        foreach ($users as $user) {
            $service->autoEnrollMandatoryForUser($user, $admin?->id);
        }

        // Mark some completed for realistic stats.
        // compliance@ completes everything (shows high completion rate).
        $compliance = User::where('email', 'compliance@example.com')->first();
        if ($compliance !== null) {
            $compliance->load('roles');
            $enrollments = TrainingEnrollment::where('user_id', $compliance->id)->get();
            foreach ($enrollments as $enrollment) {
                $service->markCompleted($enrollment, rand(80, 100), $admin?->id);
            }
        }

        // tester@ completes half (in_progress on the rest).
        $tester = User::where('email', 'tester@example.com')->first();
        if ($tester !== null) {
            $enrollments = TrainingEnrollment::where('user_id', $tester->id)->get();
            foreach ($enrollments->take(3) as $enrollment) {
                $service->markCompleted($enrollment, rand(60, 90), $admin?->id);
            }
            foreach ($enrollments->skip(3) as $enrollment) {
                $service->markStarted($enrollment);
            }
        }

        // riskowner@ has one overdue enrollment — set due_at in the past.
        $riskOwner = User::where('email', 'riskowner@example.com')->first();
        if ($riskOwner !== null) {
            $enrollment = TrainingEnrollment::where('user_id', $riskOwner->id)->first();
            if ($enrollment !== null) {
                $enrollment->update([
                    'due_at' => now()->subDays(5),
                    'status' => 'overdue',
                ]);
            }
        }
    }
}
