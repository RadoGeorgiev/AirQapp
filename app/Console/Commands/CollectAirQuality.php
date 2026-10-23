<?php

namespace App\Console\Commands;

use App\Services\AirQualityCollector;
use Illuminate\Console\Command;
use Throwable;

class CollectAirQuality extends Command
{
    protected $signature = 'air-quality:collect';

    protected $description = 'Fetch and store six months of hourly PM2.5 data from Open-Meteo';

    public function handle(AirQualityCollector $collector): int
    {
        try {
            $count = $collector->sync();
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error('Air-quality collection failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Stored {$count} hourly PM2.5 measurements.");

        return self::SUCCESS;
    }
}
