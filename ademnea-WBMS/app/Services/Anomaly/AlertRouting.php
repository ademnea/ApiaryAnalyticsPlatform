<?php

namespace App\Services\Anomaly;

/**
 * SRS REQ-F-IOT-17: who is notified of each anomaly type, and how.
 *
 * The dashboard channel is not listed: every incident is on the admin
 * anomaly dashboard as soon as it is recorded. What remains is email and
 * SMS for staff, and push for farmers (which also lands in the farmer app's
 * alert list).
 *
 * static_threshold_breach is not in the SRS table. It is routed like
 * frozen_sensor, since an impossible reading is a sensor fault for the
 * hardware team, not a hive problem for the farmer.
 */
final class AlertRouting
{
    public const ADMIN = 'admin';
    public const HARDWARE_TEAM = 'hardware_team';
    public const FARMER = 'farmer';

    private const ROUTES = [
        'device_offline' => [self::ADMIN => ['email'], self::HARDWARE_TEAM => ['email']],
        'low_battery' => [self::ADMIN => ['email'], self::HARDWARE_TEAM => ['email']],
        'critical_battery' => [self::ADMIN => ['email'], self::HARDWARE_TEAM => ['email', 'sms']],
        'weak_signal' => [],
        'frozen_sensor' => [self::ADMIN => ['email'], self::HARDWARE_TEAM => ['email']],
        'static_threshold_breach' => [self::ADMIN => ['email'], self::HARDWARE_TEAM => ['email']],
        'statistical_deviation' => [self::ADMIN => ['email'], self::FARMER => ['push']],
        'high_anomaly_rate' => [self::ADMIN => ['email']],
        'firmware_outdated' => [],
        'storage_full' => [self::ADMIN => ['email'], self::HARDWARE_TEAM => ['email']],
        'reboot_loop' => [self::ADMIN => ['email'], self::HARDWARE_TEAM => ['email']],
        'submission_delay' => [self::FARMER => ['push']],
    ];

    /** A device back online (UC-IOT-12, Alternative Flow B). */
    public const RECOVERY = [self::ADMIN => ['email'], self::HARDWARE_TEAM => ['email']];

    /** A malformed heartbeat (UC-IOT-01, Alternative Flow B). */
    public const MALFORMED_HEARTBEAT = [self::HARDWARE_TEAM => ['email']];

    /**
     * Unknown types (e.g. future ML layers) go to the admin by email rather
     * than being dropped.
     *
     * @return array<string, array<int, string>> recipient type => channels
     */
    public static function for(string $anomalyType): array
    {
        return self::ROUTES[$anomalyType] ?? [self::ADMIN => ['email']];
    }

    /** Recommended action for the email body. */
    public static function recommendedAction(string $anomalyType): string
    {
        return match ($anomalyType) {
            'device_offline' => 'Check the unit\'s power supply and network connection on site.',
            'submission_delay' => 'The unit is reporting less often than expected. Check its power and signal.',
            'low_battery' => 'Plan a battery replacement or check the charging circuit.',
            'critical_battery' => 'Replace or recharge the battery now. The unit will shut down soon.',
            'weak_signal' => 'Check the antenna placement or the network coverage at the apiary.',
            'storage_full' => 'Free up storage on the unit and check that uploads are completing.',
            'reboot_loop' => 'Inspect the unit for an unstable power supply or a firmware crash.',
            'frozen_sensor' => 'The sensor keeps repeating the same value. Reseat or replace it.',
            'static_threshold_breach' => 'The value is physically impossible. Inspect the sensor.',
            'statistical_deviation' => 'Readings are unusual for this colony. Check the hive.',
            'high_anomaly_rate' => 'Many recent readings are untrustworthy. Inspect the unit\'s sensors.',
            'firmware_outdated' => 'Schedule a firmware update.',
            default => 'Review the incident on the anomaly dashboard.',
        };
    }
}
