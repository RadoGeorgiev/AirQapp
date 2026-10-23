<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clean Air Finder — Bulgaria</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #172b2a;
            background: #f3f7f3;
            font-synthesis: none;
        }
        * { box-sizing: border-box; }
        body { margin: 0; }
        main { width: min(980px, calc(100% - 32px)); margin: 0 auto; padding: 54px 0 42px; }
        .eyebrow { color: #27745f; font-size: .76rem; font-weight: 750; letter-spacing: .12em; text-transform: uppercase; }
        h1 { max-width: 650px; margin: 12px 0; font-size: clamp(2.3rem, 6vw, 4.2rem); line-height: 1.02; letter-spacing: -.055em; }
        .intro { max-width: 650px; color: #5b706b; font-size: 1.08rem; line-height: 1.65; }
        .panel { margin-top: 30px; padding: 24px; border: 1px solid #dce7de; border-radius: 18px; background: #fff; box-shadow: 0 14px 38px #203d3210; }
        form { display: grid; grid-template-columns: 1fr 1fr auto; align-items: end; gap: 14px; }
        label { display: grid; gap: 8px; color: #405853; font-size: .86rem; font-weight: 650; }
        input { min-height: 48px; padding: 0 12px; border: 1px solid #cbd9d0; border-radius: 9px; background: #fbfdfb; color: inherit; font: inherit; }
        input:focus { outline: 3px solid #bee4d3; border-color: #31856b; }
        button { min-height: 48px; padding: 0 22px; border: 0; border-radius: 9px; background: #1f755c; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        button:hover { background: #175b47; }
        .error, .notice { margin: 16px 0 0; padding: 13px 15px; border-radius: 9px; }
        .error { background: #fff0ed; color: #a33c29; }
        .notice { background: #eef7f1; color: #486459; }
        h2 { margin: 35px 0 5px; font-size: 1.55rem; letter-spacing: -.025em; }
        .subheading { color: #6b7d77; }
        .quality-guide { margin-top: 25px; padding-top: 20px; border-top: 1px solid #e9efea; }
        .quality-guide h2 { margin: 0; font-size: 1.12rem; }
        .guide-note { margin: 7px 0 14px; color: #6b7d77; font-size: .84rem; line-height: 1.5; }
        .guide-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 9px; }
        .guide-item { display: grid; grid-template-columns: 12px minmax(0, 1fr); align-items: start; gap: 10px; padding: 11px; border: 1px solid #e9efea; border-radius: 10px; }
        .guide-swatch, .level-dot { width: 11px; height: 11px; flex: 0 0 11px; border-radius: 50%; background: currentColor; }
        .guide-item strong { display: block; font-size: .82rem; }
        .guide-item small { display: block; margin-top: 2px; color: inherit; opacity: .8; font-size: .75rem; }
        .guide-range { display: block; margin-top: 5px; font-size: .74rem; font-weight: 700; }
        .level-good { background: #e1f5e9; color: #17683b; }
        .level-moderate { background: #fff3c4; color: #795600; }
        .level-sensitive { background: #ffead7; color: #914512; }
        .level-unhealthy { background: #ffe0dd; color: #a32d27; }
        .level-very-unhealthy { background: #eee2ff; color: #6530a0; }
        .level-hazardous { background: #4b102d; color: #fff; }
        .winner { display: flex; align-items: center; justify-content: space-between; gap: 18px; margin-top: 20px; padding: 22px 24px; border: 1px solid currentColor; border-radius: 14px; }
        .winner-label { opacity: .8; font-size: .79rem; font-weight: 750; letter-spacing: .08em; text-transform: uppercase; }
        .winner-name { margin-top: 5px; font-size: 1.7rem; font-weight: 750; }
        .winner-value { text-align: right; font-size: 1.8rem; font-weight: 750; white-space: nowrap; }
        .winner-value small { display: block; color: inherit; font-size: .76rem; font-weight: 550; }
        .level-pill { display: inline-flex; align-items: center; gap: 7px; padding: 6px 9px; border-radius: 999px; font-size: .82rem; font-weight: 750; white-space: nowrap; }
        .level-pill .level-dot { width: 8px; height: 8px; flex-basis: 8px; }
        .level-hazardous .level-pill { background: #fff; color: #4b102d; }
        table { width: 100%; margin-top: 18px; border-collapse: collapse; text-align: left; }
        th, td { padding: 14px 12px; border-bottom: 1px solid #e9efea; }
        th { color: #71817b; font-size: .74rem; letter-spacing: .08em; text-transform: uppercase; }
        td:last-child, th:last-child { text-align: right; }
        .winner-row td { font-weight: 700; }
        .winner-row td:nth-child(2) { color: #195d47; }
        .rank { color: #85948d; }
        footer { margin-top: 36px; color: #75847e; font-size: .81rem; line-height: 1.7; }
        a { color: #26735b; }
        @media (max-width: 620px) {
            main { padding-top: 32px; }
            .panel { padding: 18px; }
            form { grid-template-columns: 1fr 1fr; }
            button { grid-column: 1 / -1; }
            .winner { align-items: flex-start; flex-direction: column; }
            .winner-value { text-align: left; }
            .guide-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            th, td { padding: 12px 7px; font-size: .9rem; }
            .level-pill { align-items: flex-start; flex-direction: column; gap: 3px; border-radius: 9px; white-space: normal; }
            .level-pill .level-dot { display: none; }
        }
        @media (max-width: 390px) { .guide-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<main>
    <div class="eyebrow">Bulgaria · air quality</div>
    <h1>Find the cleanest air.</h1>
    <p class="intro">Compare average hourly PM2.5 across five Bulgarian cities using locally stored air-quality data.</p>

    <section class="panel" aria-label="Choose a date range">
        <form method="get" action="{{ route('home') }}">
            <label for="start_date">
                From
                <input id="start_date" name="start_date" type="date" required
                       min="{{ $earliestDate }}" max="{{ $latestDate }}"
                       value="{{ $startDate ?? today(config('air-quality.timezone'))->subDays(6)->toDateString() }}">
            </label>
            <label for="end_date">
                To
                <input id="end_date" name="end_date" type="date" required
                       min="{{ $earliestDate }}" max="{{ $latestDate }}"
                       value="{{ $endDate ?? today(config('air-quality.timezone'))->toDateString() }}">
            </label>
            <button type="submit">Compare cities</button>
        </form>

        @if ($errors->any())
            <div class="error" role="alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <section class="quality-guide" aria-labelledby="quality-guide-heading">
            <h2 id="quality-guide-heading">How to read the PM2.5 colors</h2>
            <p class="guide-note">
                Bands are based on U.S. EPA PM2.5 AQI concentration breakpoints. We apply them to the selected-period average for context; this is not an official AQI reading or health assessment.
            </p>
            <div class="guide-grid">
                @foreach (config('air-quality.pm2_5_levels') as $key => $level)
                    <div class="guide-item level-{{ $key }}">
                        <span class="guide-swatch" aria-hidden="true"></span>
                        <span>
                            <strong>{{ $level['label'] }}</strong>
                            <small>{{ $level['description'] }}</small>
                            <span class="guide-range">{{ $level['range'] }}</span>
                        </span>
                    </div>
                @endforeach
            </div>
        </section>

        @if ($results !== null)
            @php($winner = collect($results)->first(fn (array $city) => $city['average_pm2_5'] !== null))
            <h2>City ranking</h2>
            <p class="subheading">{{ $startDate }} to {{ $endDate }} · average PM2.5 for the selected hours</p>

            @if ($winner)
                <div class="winner level-{{ $winner['air_quality']['key'] }}">
                    <div>
                        <div class="winner-label">Cleanest city for this period</div>
                        <div class="winner-name">{{ $winner['name'] }}</div>
                    </div>
                    <div class="winner-value">
                        {{ number_format($winner['average_pm2_5'], 2) }}
                        <small>μg/m³ average PM2.5 · {{ $winner['air_quality']['label'] }}</small>
                    </div>
                </div>
            @else
                <div class="notice">No measurements are available for this date range yet. Please try another period.</div>
            @endif

            <table>
                <thead>
                <tr>
                    <th scope="col">Rank</th>
                    <th scope="col">City</th>
                    <th scope="col">Average PM2.5</th>
                    <th scope="col">Hourly readings</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($results as $city)
                    <tr @class(['winner-row' => $city['rank'] === 1])>
                        <td class="rank">{{ $city['rank'] ?? '—' }}</td>
                        <td>{{ $city['name'] }}</td>
                        <td>
                            @if ($city['average_pm2_5'] === null)
                                No data
                            @else
                                <span class="level-pill level-{{ $city['air_quality']['key'] }}">
                                    <span class="level-dot" aria-hidden="true"></span>
                                    {{ number_format($city['average_pm2_5'], 2) }} μg/m³ · {{ $city['air_quality']['label'] }}
                                </span>
                            @endif
                        </td>
                        <td>{{ number_format($city['measurement_count']) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <footer>
        Data from <a href="https://open-meteo.com/" rel="noreferrer">Open-Meteo</a>, using city-center coordinates and hourly PM2.5.
        PM2.5 is modelled air-quality data, not a network of street-level sensors. Historical availability is limited to the last six months.
    </footer>
</main>
</body>
</html>
