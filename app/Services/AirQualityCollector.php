<?php

namespace App\Services;

use App\Models\AirQualitySync;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class AirQualityCollector
{
    /**
     * Download and atomically store the configured six-month window.
     *
     * @throws ConnectionException
     */
    public function sync(): int
    {
        $timezone = config('air-quality.timezone');
        $now = CarbonImmutable::now($timezone);
        $today = $now->startOfDay();
        $from = $today->subMonthsNoOverflow(config('air-quality.history_months'));
        $to = $today;

        $sync = AirQualitySync::create([
            'status' => 'running',
            'requested_from' => $from->toDateString(),
            'requested_to' => $to->toDateString(),
            'started_at' => now(),
        ]);

        try {
            $measurements = $this->download($from, $to, $now);

            DB::transaction(function () use ($measurements, $from, $sync): void {
                foreach (array_chunk($measurements, 1000) as $chunk) {
                    DB::table('air_quality_measurements')->upsert(
                        $chunk,
                        ['city', 'recorded_at'],
                        ['pm2_5'],
                    );
                }

                DB::table('air_quality_measurements')
                    ->where('recorded_at', '<', $from->utc()->format('Y-m-d H:i:s'))
                    ->delete();

                $sync->update([
                    'status' => 'succeeded',
                    'rows_synced' => count($measurements),
                    'finished_at' => now(),
                ]);
            });

            Log::info('Air-quality data collection completed.', [
                'sync_id' => $sync->id,
                'rows_synced' => count($measurements),
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ]);

            return count($measurements);
        } catch (Throwable $exception) {
            $sync->update([
                'status' => 'failed',
                'error' => substr($exception->getMessage(), 0, 5000),
                'finished_at' => now(),
            ]);

            Log::error('Air-quality data collection failed; stored measurements were preserved.', [
                'sync_id' => $sync->id,
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    /**
     * @return list<array{city: string, recorded_at: string, pm2_5: float}>
     */
    private function download(CarbonImmutable $from, CarbonImmutable $to, CarbonImmutable $now): array
    {
        $cities = config('air-quality.cities');
        $latitude = implode(',', array_column($cities, 'latitude'));
        $longitude = implode(',', array_column($cities, 'longitude'));

        $response = Http::acceptJson()
            ->timeout(60)
            ->retry(2, 500)
            ->get(config('air-quality.endpoint'), [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'hourly' => 'pm2_5',
                'start_date' => $from->toDateString(),
                'end_date' => $to->toDateString(),
                'timezone' => config('air-quality.timezone'),
                'timeformat' => 'unixtime',
            ])
            ->throw();

        $locations = $response->json();

        if (! is_array($locations) || count($locations) !== count($cities) || ! array_is_list($locations)) {
            throw new RuntimeException('Open-Meteo returned an unexpected number of city locations.');
        }

        $measurements = [];

        foreach (array_values($cities) as $index => $city) {
            $location = $locations[$index] ?? null;

            if (
                ! is_array($location)
                || ! isset($location['latitude'], $location['longitude'])
                || abs((float) $location['latitude'] - $city['latitude']) > 0.15
                || abs((float) $location['longitude'] - $city['longitude']) > 0.15
            ) {
                throw new RuntimeException("Open-Meteo returned an invalid location for {$city['name']}.");
            }

            $times = $location['hourly']['time'] ?? null;
            $values = $location['hourly']['pm2_5'] ?? null;

            if (
                ! is_array($times)
                || ! is_array($values)
                || ! array_is_list($times)
                || ! array_is_list($values)
                || count($times) !== count($values)
            ) {
                throw new RuntimeException("Open-Meteo returned incomplete hourly PM2.5 data for {$city['name']}.");
            }

            foreach ($times as $hour => $time) {
                if (! is_int($time) && (! is_string($time) || ! ctype_digit($time))) {
                    throw new RuntimeException("Open-Meteo returned an invalid timestamp for {$city['name']}.");
                }

                $recordedAt = CarbonImmutable::createFromTimestampUTC((int) $time)
                    ->setTimezone(config('air-quality.timezone'));

                if (
                    $recordedAt->lessThan($from)
                    || $recordedAt->greaterThan($to->endOfDay())
                ) {
                    throw new RuntimeException("Open-Meteo returned an out-of-range timestamp for {$city['name']}.");
                }

                if ($recordedAt->greaterThanOrEqualTo($now->startOfHour())) {
                    continue;
                }

                $value = $values[$hour];

                if ($value === null) {
                    continue;
                }

                if (! is_numeric($value) || ! is_finite((float) $value) || (float) $value < 0) {
                    throw new RuntimeException("Open-Meteo returned an invalid PM2.5 value for {$city['name']}.");
                }

                $measurements[] = [
                    'city' => strtolower($city['name']),
                    'recorded_at' => $recordedAt->utc()->format('Y-m-d H:i:s'),
                    'pm2_5' => round((float) $value, 3),
                ];
            }
        }

        if ($measurements === []) {
            throw new RuntimeException('Open-Meteo returned no usable PM2.5 observations.');
        }

        return $measurements;
    }
}
