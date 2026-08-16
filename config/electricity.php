<?php

return [
    'region' => env('PRICE_REGION', 'EE'),
    'elering_api_base_url' => env('ELERING_API_BASE_URL', 'https://dashboard.elering.ee/api/nps/price'),
    'cache_ttl_seconds' => (int) env('CACHE_TTL_SECONDS', 1800),
    'vat_rate' => (float) env('VAT_RATE', 0.24),
    'network_fee_eur_per_mwh' => (float) env('NETWORK_FEE_EUR_PER_MWH', 0),
    'supplier_margin_eur_per_mwh' => (float) env('SUPPLIER_MARGIN_EUR_PER_MWH', 0),
    'recipient_email' => env('RECIPIENT_EMAIL', ''),
    'github_repo_url' => env('GITHUB_REPO_URL', ''),
];
