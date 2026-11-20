<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Dữ liệu demo cho CRM: php artisan db:seed --class=LeadSeeder
 */
class LeadSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::where('active', true)->pluck('id');

        Lead::factory()->count(60)->make()->each(function (Lead $lead) use ($userIds) {
            // ~25% lead chưa gán tư vấn viên
            $lead->assigned_to = $userIds->isNotEmpty() && fake()->boolean(75) ? $userIds->random() : null;
            // Lead từ nguồn Holland => có điểm trắc nghiệm
            if ($lead->source === 'holland') {
                foreach (['r', 'i', 'a', 's', 'e', 'c'] as $l) {
                    $lead->{"holland_{$l}"} = fake()->numberBetween(20, 95);
                }
            }
            $lead->save();
        });
    }
}
