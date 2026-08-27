<?php

use App\Contracts\ApiaryDirectoryServiceContract;
use App\Models\Apiary;
use App\Models\Farmer;
use App\Models\Hive;
use App\Models\IotDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiaryDirectoryServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_active_apiaries_with_their_primary_farmer(): void
    {
        $farmer = Farmer::factory()->active()->create([
            'first_name' => 'Prossy',
            'last_name' => 'Nakato',
        ]);

        Apiary::factory()->active()->create([
            'name' => 'Mukono Central Apiary',
            'country' => 'UG',
            'farmer_id' => $farmer->id,
        ]);

        Apiary::factory()->inactive()->create([
            'name' => 'Old Apiary',
            'country' => 'UG',
        ]);

        $service = app(ApiaryDirectoryServiceContract::class);
        $result = $service->listApiariesWithPrimaryFarmer();

        $this->assertCount(1, $result);
        $this->assertEquals('Mukono Central Apiary', $result->first()->name);
        $this->assertEquals('Prossy Nakato', $result->first()->farmer_name);
        $this->assertEquals('UG', $result->first()->country);
    }

    #[Test]
    public function it_marks_unassigned_apiaries_with_unassigned_farmer_name(): void
    {
        Apiary::factory()->active()->create([
            'name' => 'Unassigned Apiary',
            'country' => 'UG',
            'farmer_id' => null,
        ]);

        $service = app(ApiaryDirectoryServiceContract::class);
        $result = $service->listApiariesWithPrimaryFarmer();

        $this->assertCount(1, $result);
        $this->assertEquals('Unassigned', $result->first()->farmer_name);
    }

    #[Test]
    public function it_lists_hives_available_for_device_type_excluding_already_assigned(): void
    {
        $apiary = Apiary::factory()->active()->create();
        $hive = Hive::factory()->forApiary($apiary)->create();
        $otherHive = Hive::factory()->forApiary($apiary)->create();

        IotDevice::factory()->create([
            'hive_id' => $hive->id,
            'device_type' => 'numeric_sensor',
            'active_flag' => true,
        ]);

        $service = app(ApiaryDirectoryServiceContract::class);
        $result = $service->listHivesAvailableForDeviceType($apiary->id, 'numeric_sensor');

        $this->assertCount(1, $result);
        $this->assertEquals($otherHive->id, $result->first()->id);
        $this->assertEquals($otherHive->hybrid_identifier, $result->first()->hybrid_code);
    }

    #[Test]
    public function it_includes_hives_when_no_device_of_that_type_is_assigned(): void
    {
        $apiary = Apiary::factory()->active()->create();
        $hive = Hive::factory()->forApiary($apiary)->create();

        IotDevice::factory()->create([
            'hive_id' => null,
            'device_type' => 'media_capture',
            'active_flag' => true,
        ]);

        $service = app(ApiaryDirectoryServiceContract::class);
        $result = $service->listHivesAvailableForDeviceType($apiary->id, 'numeric_sensor');

        $this->assertCount(1, $result);
        $this->assertEquals($hive->id, $result->first()->id);
    }

    #[Test]
    public function it_excludes_hives_with_inactive_devices_of_the_same_type(): void
    {
        $apiary = Apiary::factory()->active()->create();
        $hive = Hive::factory()->forApiary($apiary)->create();

        IotDevice::factory()->create([
            'hive_id' => $hive->id,
            'device_type' => 'numeric_sensor',
            'active_flag' => false,
        ]);

        $service = app(ApiaryDirectoryServiceContract::class);
        $result = $service->listHivesAvailableForDeviceType($apiary->id, 'numeric_sensor');

        $this->assertCount(1, $result);
        $this->assertEquals($hive->id, $result->first()->id);
    }

    #[Test]
    public function it_returns_empty_collection_for_apiary_with_no_available_hives(): void
    {
        $apiary = Apiary::factory()->active()->create();

        $service = app(ApiaryDirectoryServiceContract::class);
        $result = $service->listHivesAvailableForDeviceType($apiary->id, 'numeric_sensor');

        $this->assertTrue($result->isEmpty());
    }
}
