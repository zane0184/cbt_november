<?php

return [
    // Explicit, temporary 2026 pilot mode. Publisher licensing remains the default.
    'mode' => env('CBT_LICENSE_MODE', 'PUBLISHER'),
    'pre_activation_2026' => env('CBT_LICENSE_MODE', 'PUBLISHER') === 'PRE_ACTIVATION_2026',
    // Pilot issuance closes at the end of 2026; each installation receives at most 180 days.
    'pre_activation_issue_deadline' => '2026-12-31T23:59:59+07:00',
    'pre_activation_duration_days' => 180,
    // Test-only bypass; requires APP_ENV=testing and is false by default.
    'testing_bypass' => (bool) env('CBT_LICENSE_TEST_BYPASS', false),
    'publisher_url' => rtrim(env('CBT_PUBLISHER_URL', 'https://portal.rdmdigital.id'), '/'),
    'product_code' => env('CBT_PRODUCT_CODE', 'CBT-MI'),
    'product_version' => env('CBT_PRODUCT_VERSION', '1.0.0'),
    'audience' => env('CBT_LICENSE_AUDIENCE', 'cbt-madrasah-standalone'),
    'clock_skew_seconds' => (int) env('CBT_LICENSE_CLOCK_SKEW_SECONDS', 300),
    'lease_refresh_interval_hours' => (int) env('CBT_LICENSE_REFRESH_INTERVAL_HOURS', 24),
    'offline_lease_max_hours' => (int) env('CBT_LICENSE_OFFLINE_LEASE_MAX_HOURS', 72),
];
