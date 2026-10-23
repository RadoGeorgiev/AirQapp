<?php

namespace App\Services;

class Pm25Level
{
    /**
     * @return array{key: string, label: string}|null
     */
    public function forValue(?float $value): ?array
    {
        if ($value === null) {
            return null;
        }

        foreach (config('air-quality.pm2_5_levels') as $key => $level) {
            if ($level['maximum'] === null || $value <= $level['maximum']) {
                return [
                    'key' => $key,
                    'label' => $level['label'],
                ];
            }
        }

        throw new \LogicException('PM2.5 concentration levels must include an unbounded final level.');
    }
}
