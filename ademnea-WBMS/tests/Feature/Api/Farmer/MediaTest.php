<?php

namespace Tests\Feature\Api\Farmer;

use App\Contracts\MediaUploadStorageContract;
use App\Models\Apiary;
use App\Models\Hive;
use App\Models\HiveAudio;
use App\Models\HivePhoto;
use App\Models\IotDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * UC-FAPI-10 to 12.
 */
class MediaTest extends FarmerApiTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Deterministic URLs, and no network calls to S3 from a test.
        $this->app->bind(MediaUploadStorageContract::class, fn () => new class implements MediaUploadStorageContract {
            public function generateUploadUrl(string $objectKey, string $contentType): string
            {
                return 'https://uploads.test/' . $objectKey;
            }

            public function objectExists(string $objectKey): bool
            {
                return true;
            }

            public function publicUrl(string $objectKey): string
            {
                return 'https://media.test/' . $objectKey;
            }
        });
    }

    private function hiveOwnedBy(int $farmerId): Hive
    {
        $apiary = Apiary::factory()->create(['farmer_id' => $farmerId]);

        return Hive::factory()->create(['apiary_id' => $apiary->id]);
    }

    /**
     * url was previously built from a `path` attribute that does not exist on
     * these models, so every media item came back with url "/storage/".
     */
    public function test_photo_url_is_resolved_from_the_stored_object_key(): void
    {
        [$user, $farmer] = $this->makeFarmer();
        $hive = $this->hiveOwnedBy($farmer->id);
        $device = IotDevice::factory()->create();

        HivePhoto::create([
            'hive_id'     => $hive->id,
            'device_id'   => $device->id,
            'file_path'   => 'media/photo-1.jpg',
            'recorded_at' => now(),
        ]);

        $this->authed($user)
            ->getJson("/api/v1/farmer/hives/{$hive->id}/photos")
            ->assertOk()
            ->assertJsonPath('data.0.url', 'https://media.test/media/photo-1.jpg');
    }

    /**
     * Audio previously returned a bare list capped at 20 with no meta and
     * ignored per_page, so the client had to special-case it.
     */
    public function test_audio_is_paginated_like_photos_and_videos(): void
    {
        [$user, $farmer] = $this->makeFarmer();
        $hive = $this->hiveOwnedBy($farmer->id);
        $device = IotDevice::factory()->create();

        foreach (range(1, 12) as $n) {
            HiveAudio::create([
                'hive_id'     => $hive->id,
                'device_id'   => $device->id,
                'file_path'   => "media/audio-{$n}.wav",
                'recorded_at' => now(),
            ]);
        }

        $response = $this->authed($user)
            ->getJson("/api/v1/farmer/hives/{$hive->id}/audio?per_page=5")
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);

        $this->assertCount(5, $response->json('data'));
        $this->assertSame(12, $response->json('meta.total'));
        $this->assertStringStartsWith('https://media.test/', $response->json('data.0.url'));
    }

    public function test_media_for_another_farmers_hive_is_not_reachable(): void
    {
        [$user] = $this->makeFarmer();
        [, $otherFarmer] = $this->makeFarmer('other@example.com', idOffset: 5);

        $theirHive = $this->hiveOwnedBy($otherFarmer->id);

        $this->authed($user)
            ->getJson("/api/v1/farmer/hives/{$theirHive->id}/photos")
            ->assertStatus(404);
    }
}
