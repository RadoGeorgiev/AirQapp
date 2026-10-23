<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AirQualityRanking
{
    /**
     * @return list<array{name: string, average_pm2_5: ?float, measurement_count: int, rank: ?int}>
     */
    public function forPeriod(string $startDate, string $endDate): array
    {
        $timezone = config('air-quality.timezone');
        $start = CarbonImmutable::parse($startDate, $timezone)->startOfDay()->utc();
        $end = CarbonImmutable::parse($endDate, $timezone)->endOfDay()->utc();

        $averages = DB::table('air_quality_measurements')
            ->whereBetween('recorded_at', [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')])
            ->selectRaw('city, AVG(pm2_5) AS average_pm2_5, COUNT(*) AS measurement_count')
            ->groupBy('city')
            ->get()
            ->keyBy('city');

        $ranking = collect(config('air-quality.cities'))
            ->map(function (array $city, string $slug) use ($averages): array {
                $average = $averages->get($slug);

                return [
                    'name' => $city['name'],
                    'average_pm2_5' => $average === null ? null : round((float) $average->average_pm2_5, 2),
                    'measurement_count' => $average === null ? 0 : (int) $average->measurement_count,
                    'rank' => null,
                ];
            })
            ->sort(function (array $left, array $right): int {
                if ($left['average_pm2_5'] === null) {
                    return $right['average_pm2_5'] === null ? $left['name'] <=> $right['name'] : 1;
                }

                if ($right['average_pm2_5'] === null) {
                    return -1;
                }

                return $left['average_pm2_5'] <=> $right['average_pm2_5']
                    ?: $left['name'] <=> $right['name'];
            })
            ->values();

        $rank = 0;

        return $ranking->map(function (array $city) use (&$rank): array {
            if ($city['average_pm2_5'] !== null) {
                $city['rank'] = ++$rank;
            }

            return $city;
        })->all();
    }
}
