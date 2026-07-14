<?php

namespace Database\Seeders;

use App\Models\FeedbackCategory;
use Illuminate\Database\Seeder;

/**
 * FeedbackCategorySeeder
 *
 * Seeds the standard set of feedback categories used by the public feedback form
 * and the admin feedback management module.
 *
 * This seeder is IDEMPOTENT — running it multiple times will not create duplicates.
 *
 * Usage:
 *   php artisan db:seed --class=FeedbackCategorySeeder
 */
class FeedbackCategorySeeder extends Seeder
{
    /**
     * The standard feedback categories.
     * Add new entries here when introducing new feedback types.
     */
    private array $categories = [
        [
            'name' => 'System issue',
            'description' => 'Report a bug or technical problem with the platform.',
        ],
        [
            'name' => 'Observation',
            'description' => 'Share an observation related to hives, colonies, or monitoring.',
        ],
        [
            'name' => 'Suggestion',
            'description' => 'Propose an improvement or new feature.',
        ],
        [
            'name' => 'General inquiry',
            'description' => 'Ask a general question or get in touch.',
        ],
    ];

    public function run(): void
    {
        foreach ($this->categories as $category) {
            FeedbackCategory::firstOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description']]
            );
        }

        $this->command->info('✓ Feedback categories seeded.');
    }
}