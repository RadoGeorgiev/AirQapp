<?php

namespace Tests\Feature;

use App\Models\AirQualityMeasurement;
use App\Models\AirQualitySync;
use App\Services\AirQualityCollector;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AirQualityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-21 11:00:00', 'Europe/Sofia'));
    }

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_collector_requests_all_cities_and_stores_only_non_future_observations(): void
    {
        DB::table('air_quality_measurements')->insert([
            'city' => 'sofia',
            'recorded_at' => '2026-04-20 20:00:00',
            'pm2_5' => 50,
        ]);
        Http::fake([
            '*' => Http::response($this->apiResponse(), 200),
        ]);

        $count = app(AirQualityCollector::class)->sync();
        app(AirQualityCollector::class)->sync();

        $this->assertSame(10, $count);
        $this->assertDatabaseCount('air_quality_measurements', 10);
        $this->assertDatabaseMissing('air_quality_measurements', [
            'recorded_at' => '2026-04-20 20:00:00',
        ]);
        $this->assertDatabaseHas('air_quality_measurements', [
            'city' => 'sofia',
            'recorded_at' => '2026-10-20 21:00:00',
            'pm2_5' => 20,
        ]);
        $this->assertDatabaseHas('air_quality_syncs', [
            'status' => 'succeeded',
            'rows_synced' => 10,
        ]);

        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'latitude=42.6977%2C42.1354%2C43.2141%2C42.5048%2C43.8356')
            && str_contains($request->url(), 'start_date=2026-04-21')
            && str_contains($request->url(), 'end_date=2026-10-21')
            && str_contains($request->url(), 'timeformat=unixtime'));
    }

    public function test_collector_preserves_both_hours_when_local_time_repeats_at_dst_end(): void
    {
        $this->travelTo(Carbon::parse('2026-10-26 11:00:00', 'Europe/Sofia'));
        $payload = $this->apiResponse();
        $times = [
            CarbonImmutable::parse('2026-10-25 00:00:00', 'UTC')->timestamp,
            CarbonImmutable::parse('2026-10-25 01:00:00', 'UTC')->timestamp,
            CarbonImmutable::parse('2026-10-25 02:00:00', 'UTC')->timestamp,
        ];

        foreach ($payload as &$location) {
            $location['hourly']['time'] = $times;
        }

        Http::fake(['*' => Http::response($payload, 200)]);

        $this->assertSame(15, app(AirQualityCollector::class)->sync());
        $this->assertDatabaseHas('air_quality_measurements', [
            'city' => 'sofia',
            'recorded_at' => '2026-10-25 00:00:00',
        ]);
        $this->assertDatabaseHas('air_quality_measurements', [
            'city' => 'sofia',
            'recorded_at' => '2026-10-25 01:00:00',
        ]);
    }

    public function test_collector_failure_keeps_existing_measurements_and_records_failure(): void
    {
        DB::table('air_quality_measurements')->insert([
            'city' => 'sofia',
            'recorded_at' => '2026-10-20 00:00:00',
            'pm2_5' => 12.5,
        ]);
        Http::fake(['*' => Http::response(['error' => true], 503)]);

        $this->artisan('air-quality:collect')->assertExitCode(1);

        $this->assertDatabaseCount('air_quality_measurements', 1);
        $this->assertDatabaseHas('air_quality_measurements', [
            'city' => 'sofia',
            'pm2_5' => 12.5,
        ]);
        $this->assertDatabaseHas('air_quality_syncs', [
            'status' => 'failed',
        ]);
    }

    public function test_malformed_api_locations_do_not_replace_saved_measurements(): void
    {
        DB::table('air_quality_measurements')->insert([
            'city' => 'sofia',
            'recorded_at' => '2026-10-20 00:00:00',
            'pm2_5' => 12.5,
        ]);
        $payload = $this->apiResponse();
        $payload[0]['latitude'] = 0;
        Http::fake(['*' => Http::response($payload, 200)]);

        $this->artisan('air-quality:collect')->assertExitCode(1);

        $this->assertDatabaseCount('air_quality_measurements', 1);
        $this->assertDatabaseHas('air_quality_syncs', [
            'status' => 'failed',
        ]);
    }

    public function test_invalid_api_timestamps_fail_the_sync_without_writing_partial_data(): void
    {
        DB::table('air_quality_measurements')->insert([
            'city' => 'sofia',
            'recorded_at' => '2026-10-20 00:00:00',
            'pm2_5' => 12.5,
        ]);
        $payload = $this->apiResponse();
        $payload[0]['hourly']['time'][0] = '2026-02-30T00:00';
        Http::fake(['*' => Http::response($payload, 200)]);

        $this->artisan('air-quality:collect')->assertExitCode(1);

        $this->assertDatabaseCount('air_quality_measurements', 1);
        $this->assertDatabaseHas('air_quality_syncs', [
            'status' => 'failed',
        ]);
    }

    public function test_ranking_aggregates_by_city_and_returns_all_five_cities(): void
    {
        $values = [
            'sofia' => 20,
            'plovdiv' => 5,
            'varna' => 10,
            'burgas' => 30,
            'ruse' => 15,
        ];

        foreach ($values as $city => $pm25) {
            AirQualityMeasurement::create([
                'city' => $city,
                'recorded_at' => '2026-10-20 12:00:00',
                'pm2_5' => $pm25,
            ]);
        }

        $response = $this->getJson('/api/air-quality?start_date=2026-10-20&end_date=2026-10-20');

        $response->assertOk()
            ->assertJsonPath('cleanest_city.name', 'Plovdiv')
            ->assertJsonPath('cleanest_city.average_pm2_5', 5)
            ->assertJsonPath('ranking.0.rank', 1)
            ->assertJsonPath('ranking.4.name', 'Burgas')
            ->assertJsonPath('unit', 'μg/m³');

        $this->assertCount(5, $response->json('ranking'));
    }

    public function test_dashboard_displays_the_ranking_for_the_selected_dates(): void
    {
        AirQualityMeasurement::create([
            'city' => 'plovdiv',
            'recorded_at' => '2026-10-20 12:00:00',
            'pm2_5' => 5,
        ]);

        $this->get('/?start_date=2026-10-20&end_date=2026-10-20')
            ->assertOk()
            ->assertSee('Cleanest city for this period')
            ->assertSee('Plovdiv')
            ->assertSee('5.00 μg/m³');
    }

    public function test_invalid_date_ranges_return_clear_validation_errors(): void
    {
        $this->getJson('/api/air-quality?start_date=2026-10-22&end_date=2026-10-23')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date', 'end_date']);

        $this->getJson('/api/air-quality?start_date=2026-10-10&end_date=2026-10-09')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);

        $this->get('/?start_date=2026-01-01&end_date=2026-01-02')
            ->assertRedirect()
            ->assertSessionHasErrors('start_date');
    }

    public function test_unknown_period_returns_all_cities_with_a_helpful_empty_message(): void
    {
        $this->getJson('/api/air-quality?start_date=2026-10-20&end_date=2026-10-20')
            ->assertOk()
            ->assertJsonPath('cleanest_city', null)
            ->assertJsonPath('message', 'No measurements are available for this date range yet.')
            ->assertJsonCount(5, 'ranking');
    }

    public function test_data_health_and_metrics_report_collection_state(): void
    {
        $this->getJson('/health/data')
            ->assertServiceUnavailable()
            ->assertJsonPath('status', 'degraded');

        AirQualityMeasurement::create([
            'city' => 'sofia',
            'recorded_at' => '2026-10-21 00:00:00',
            'pm2_5' => 18.2,
        ]);
        AirQualitySync::create([
            'status' => 'succeeded',
            'requested_from' => '2026-04-21',
            'requested_to' => '2026-10-21',
            'rows_synced' => 1,
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        $this->getJson('/health/data')
            ->assertOk()
            ->assertJsonPath('status', 'healthy')
            ->assertJsonPath('measurement_count', 1);

        $this->getJson('/metrics')
            ->assertOk()
            ->assertJsonPath('measurements_total', 1)
            ->assertJsonPath('measurements_by_city.0.city', 'sofia');

        AirQualitySync::create([
            'status' => 'failed',
            'requested_from' => '2026-04-21',
            'requested_to' => '2026-10-21',
            'rows_synced' => 0,
            'error' => 'API unavailable',
            'started_at' => now()->addMinute(),
            'finished_at' => now()->addMinute(),
        ]);

        $this->getJson('/health/data')
            ->assertServiceUnavailable()
            ->assertJsonPath('last_sync_status', 'failed');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function apiResponse(): array
    {
        $times = array_map(fn (string $time): int => CarbonImmutable::parse($time, 'Europe/Sofia')->timestamp, [
            '2026-10-20T00:00',
            '2026-10-21T00:00',
            '2026-10-21T12:00',
        ]);

        return collect(config('air-quality.cities'))
            ->values()
            ->map(fn (array $city, int $index): array => [
                'latitude' => round($city['latitude'], 1),
                'longitude' => round($city['longitude'], 1),
                'hourly' => [
                    'time' => $times,
                    'pm2_5' => [10 + $index, 20 + $index, 30 + $index],
                ],
            ])
            ->all();
    }
}
