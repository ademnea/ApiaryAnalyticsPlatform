<?php

namespace Database\Seeders;

use App\Models\AlertThreshold;
use Illuminate\Database\Seeder;

/**
 * REQ-F-FAPI-24: Default alert thresholds, admin-editable afterward.
 */
class AlertThresholdSeeder extends Seeder
{
    public function run(): void
    {
        AlertThreshold::updateOrCreate(
            ['key' => 'feed_required_weight_kg'],
            ['value' => '15', 'description' => 'Minimum hive weight (kg) before a feed_required alert fires.']
        );

        AlertThreshold::updateOrCreate(
            ['key' => 'malfunction_threshold_placeholder'],
            ['value' => '', 'description' => 'Placeholder — pending domain-expert review per REQ-F-FAPI-24 note.']
        );

        AlertThreshold::updateOrCreate(
            ['key' => 'critical_event_threshold_placeholder'],
            ['value' => '', 'description' => 'Placeholder — pending domain-expert review per REQ-F-FAPI-24 note.']
        );
    }
}