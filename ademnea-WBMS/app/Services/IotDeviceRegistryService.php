<?php

namespace App\Services;

use App\Models\IotAuthLog;
use App\Models\IotDevice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class IotDeviceRegistryService
{
    /**
     * Default expected reporting interval per device type, in minutes.
     * numeric_sensor devices report far more often than media_capture units.
     */
    private const DEFAULT_INTERVAL_MINUTES = [
        'numeric_sensor' => 5,
        'media_capture' => 60,
        'combo' => 5,
    ];

    /**
     * Registers a new IoT device and generates its API key.
     * The plaintext key is returned exactly once and never persisted anywhere.
     *
     * @return array{device: IotDevice, plaintext_key: string}
     */
  public function register(array $data): array
{
    $plaintextKey = Str::random(64);

    // Create a new unsaved instance
    $device = new IotDevice();

    // Fill only the attributes that are mass-assignable
    $device->fill([
        'device_code'             => $data['device_code'],
        'device_type'             => $data['device_type'],
        'hardware_team_id'        => $data['hardware_team_id'],
        'hive_id'                 => $data['hive_id'] ?? null,
        'expected_interval_minutes' => $data['expected_interval_minutes']
            ?? self::DEFAULT_INTERVAL_MINUTES[$data['device_type']]
            ?? 5,
        'hardware_revision'       => $data['hardware_revision'] ?? null,
        'firmware_notes'          => $data['firmware_notes'] ?? null,
        'status'                  => 'provisioned',
        'active_flag'             => true,
    ]);

    // Manually set the API key hash – bypasses $fillable
    $device->api_key_hash = Hash::make($plaintextKey);

    // Now save – this generates a single INSERT with all columns
    $device->save();

    $this->logAuthEvent($device, 'provisioned');

    return [
        'device'        => $device,
        'plaintext_key' => $plaintextKey,
    ];
}

    public function update(IotDevice $device, array $data): IotDevice
    {
        // api_key_hash is never touched here — see UpdateIotDeviceRequest note.
        $device->update([
            'device_type' => $data['device_type'],
            'hardware_team_id' => $data['hardware_team_id'],
            'hive_id' => $data['hive_id'] ?? null,
            'expected_interval_minutes' => $data['expected_interval_minutes'],
            'hardware_revision' => $data['hardware_revision'] ?? null,
            'firmware_notes' => $data['firmware_notes'] ?? null,
            'status' => $data['status'],
        ]);

        return $device->fresh();
    }

    public function revoke(IotDevice $device): void
    {
        $device->update(['active_flag' => false]);
        $this->logAuthEvent($device, 'revoked');
    }

    public function reactivate(IotDevice $device): void
    {
        $device->update(['active_flag' => true]);
        $this->logAuthEvent($device, 'reactivated');
    }



    /**
     * Soft-deletes the device record. Distinct from revoke(): revoke keeps
     * the device visible in the registry as inactive; delete() removes it
     * from the active list entirely (e.g. a duplicate or mistaken registration),
     * while retaining the row and its historical data per Rule 5.
     */


    public function delete(IotDevice $device): void
    {
        $device->update(['active_flag' => false]);
        $this->logAuthEvent($device, 'revoked');
        $device->delete();
    }

    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = IotDevice::query()->with('hardwareTeam', 'hive');

        if (! empty($filters['hardware_team_id'])) {
            $query->where('hardware_team_id', $filters['hardware_team_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (array_key_exists('active_flag', $filters) && $filters['active_flag'] !== null) {
            $query->where('active_flag', (bool) $filters['active_flag']);
        }

        return $query->orderByDesc('created_at')->paginate(25)->withQueryString();
    }

    /**
 * Assigns this device to a hive. A device moving from "provisioned" to
 * having a hive is, by definition, being deployed, so status is bumped
 * automatically rather than requiring the admin to remember two steps.
 */
public function assignToHive(IotDevice $device, int $hiveId): IotDevice
{
    $device->update([
        'hive_id' => $hiveId,
        'status' => $device->status === 'provisioned' ? 'deployed' : $device->status,
    ]);

    $this->logAuthEvent($device, 'assigned_to_hive');

    return $device->fresh();
}

public function unassignFromHive(IotDevice $device): void
{
    $device->update(['hive_id' => null]);
    $this->logAuthEvent($device, 'unassigned_from_hive');
}

    private function logAuthEvent(IotDevice $device, string $eventType): void
    {
        IotAuthLog::create([
            'device_id' => $device->id,
            'event_type' => $eventType,
            'ip_address' => request()->ip(),
            'endpoint' => 'admin/iot-devices',
            'created_at' => now(),
        ]);
    }
}