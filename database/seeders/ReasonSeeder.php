<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Reason;
use Illuminate\Database\Seeder;

class ReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reasons = collect([
            'Please dispute. This is not my Account',
            'I never plan to pay this Account',
            'Bankruptcy',
            'I\'m deployed in the military',
            'I\'m unemployed and would like to pay later',
            'Deceased',
            'Need Credit Counseling. Too many bills',
            'Need Consolidation Loan. Too many bills',
            'Other',
        ]);

        $now = now();

        $reasons = $reasons->map(fn (string $reason): array => [
            'label' => $reason,
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        Reason::query()->insert($reasons);
    }
}
