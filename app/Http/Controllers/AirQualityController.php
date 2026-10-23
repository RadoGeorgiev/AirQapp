<?php

namespace App\Http\Controllers;

use App\Services\AirQualityRanking;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AirQualityController extends Controller
{
    public function index(Request $request, AirQualityRanking $ranking): View
    {
        $results = null;
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if ($startDate !== null || $endDate !== null) {
            $period = $request->validate($this->periodRules());
            $results = $ranking->forPeriod($period['start_date'], $period['end_date']);
        }

        return view('air-quality.index', [
            'results' => $results,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'earliestDate' => $this->earliestDate(),
            'latestDate' => CarbonImmutable::now(config('air-quality.timezone'))->toDateString(),
        ]);
    }

    public function api(Request $request, AirQualityRanking $ranking): JsonResponse
    {
        $period = $request->validate($this->periodRules());
        $results = $ranking->forPeriod($period['start_date'], $period['end_date']);
        $available = array_values(array_filter($results, fn (array $city): bool => $city['average_pm2_5'] !== null));

        return response()->json([
            'period' => [
                'from' => $period['start_date'],
                'to' => $period['end_date'],
            ],
            'metric' => 'average_pm2_5',
            'unit' => 'μg/m³',
            'cleanest_city' => $available[0] ?? null,
            'ranking' => $results,
            'message' => $available === [] ? 'No measurements are available for this date range yet.' : null,
        ]);
    }

    public function dataHealth(): JsonResponse
    {
        $lastSync = DB::table('air_quality_syncs')->orderByDesc('started_at')->first();
        $lastSuccess = DB::table('air_quality_syncs')
            ->where('status', 'succeeded')
            ->orderByDesc('finished_at')
            ->value('finished_at');
        $latestMeasurement = DB::table('air_quality_measurements')->max('recorded_at');
        $isHealthy = $lastSuccess !== null
            && ($lastSync->status ?? null) !== 'failed'
            && CarbonImmutable::parse($lastSuccess, config('app.timezone'))
                ->greaterThanOrEqualTo(now(config('app.timezone'))->subHours(26));

        return response()->json([
            'status' => $isHealthy ? 'healthy' : 'degraded',
            'last_sync_status' => $lastSync->status ?? 'never',
            'last_successful_sync_at' => $lastSuccess,
            'latest_measurement_at_utc' => $latestMeasurement,
            'measurement_count' => DB::table('air_quality_measurements')->count(),
        ], $isHealthy ? 200 : 503);
    }

    public function metrics(): JsonResponse
    {
        return response()->json([
            'measurements_total' => DB::table('air_quality_measurements')->count(),
            'measurements_by_city' => DB::table('air_quality_measurements')
                ->selectRaw('city, COUNT(*) AS count')
                ->groupBy('city')
                ->orderBy('city')
                ->get(),
            'last_sync' => DB::table('air_quality_syncs')->orderByDesc('started_at')->first(),
            'last_successful_sync_at' => DB::table('air_quality_syncs')
                ->where('status', 'succeeded')
                ->orderByDesc('finished_at')
                ->value('finished_at'),
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function periodRules(): array
    {
        return [
            'start_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.$this->earliestDate(),
                'before_or_equal:'.CarbonImmutable::now(config('air-quality.timezone'))->toDateString(),
            ],
            'end_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
                'before_or_equal:'.CarbonImmutable::now(config('air-quality.timezone'))->toDateString(),
            ],
        ];
    }

    private function earliestDate(): string
    {
        return CarbonImmutable::now(config('air-quality.timezone'))
            ->subMonthsNoOverflow(config('air-quality.history_months'))
            ->toDateString();
    }
}
