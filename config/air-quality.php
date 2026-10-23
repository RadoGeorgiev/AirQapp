<?php

return [
    'endpoint' => env('AIR_QUALITY_API_URL', 'https://air-quality-api.open-meteo.com/v1/air-quality'),
    'timezone' => 'Europe/Sofia',
    'history_months' => 6,
    'cities' => [
        'sofia' => ['name' => 'Sofia', 'latitude' => 42.6977, 'longitude' => 23.3219],
        'plovdiv' => ['name' => 'Plovdiv', 'latitude' => 42.1354, 'longitude' => 24.7453],
        'varna' => ['name' => 'Varna', 'latitude' => 43.2141, 'longitude' => 27.9147],
        'burgas' => ['name' => 'Burgas', 'latitude' => 42.5048, 'longitude' => 27.4626],
        'ruse' => ['name' => 'Ruse', 'latitude' => 43.8356, 'longitude' => 25.9657],
    ],
];
