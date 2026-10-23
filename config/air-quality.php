<?php

return [
    'endpoint' => env('AIR_QUALITY_API_URL', 'https://air-quality-api.open-meteo.com/v1/air-quality'),
    'timezone' => 'Europe/Sofia',
    'history_months' => 6,
    'pm2_5_levels' => [
        'good' => [
            'label' => 'Good',
            'range' => '0–9.0 μg/m³',
            'description' => 'Little or no risk for most people.',
            'maximum' => 9.0,
        ],
        'moderate' => [
            'label' => 'Moderate',
            'range' => '>9.0–35.4 μg/m³',
            'description' => 'Generally acceptable; unusually sensitive people may be affected.',
            'maximum' => 35.4,
        ],
        'sensitive' => [
            'label' => 'Unhealthy for sensitive groups',
            'range' => '>35.4–55.4 μg/m³',
            'description' => 'Sensitive groups may experience health effects.',
            'maximum' => 55.4,
        ],
        'unhealthy' => [
            'label' => 'Unhealthy',
            'range' => '>55.4–125.4 μg/m³',
            'description' => 'Some people may experience health effects.',
            'maximum' => 125.4,
        ],
        'very-unhealthy' => [
            'label' => 'Very unhealthy',
            'range' => '>125.4–225.4 μg/m³',
            'description' => 'Health alert: risk is increased for everyone.',
            'maximum' => 225.4,
        ],
        'hazardous' => [
            'label' => 'Hazardous',
            'range' => '>225.4 μg/m³',
            'description' => 'Emergency-level pollution; everyone may be affected.',
            'maximum' => null,
        ],
    ],
    'cities' => [
        'sofia' => ['name' => 'Sofia', 'latitude' => 42.6977, 'longitude' => 23.3219],
        'plovdiv' => ['name' => 'Plovdiv', 'latitude' => 42.1354, 'longitude' => 24.7453],
        'varna' => ['name' => 'Varna', 'latitude' => 43.2141, 'longitude' => 27.9147],
        'burgas' => ['name' => 'Burgas', 'latitude' => 42.5048, 'longitude' => 27.4626],
        'ruse' => ['name' => 'Ruse', 'latitude' => 43.8356, 'longitude' => 25.9657],
    ],
];
