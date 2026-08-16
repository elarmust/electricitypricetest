# Electricity Price App

Laravel 12 web app that fetches day-ahead spot electricity prices for the `EE` region from the public Elering API,
computes daily statistics and cheapest/most-expensive windows, shows them on a responsive page.

## Requirements

- PHP **8.5+**
- Composer 2.x
- Docker + Docker Compose

## Run locally

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve --port=8000
# open http://localhost:8000
```

## Run with Docker (self-contained)

```bash
docker compose up --build
# open http://localhost:8000
```

## Configuration

| Variable | Meaning | Default |
|---|---|---|
| `PRICE_REGION` | Price region (EE/FI/LV/LT) | `EE` |
| `ELERING_API_BASE_URL` | Elering API base URL | `https://dashboard.elering.ee/api/nps/price` |
| `CACHE_TTL_SECONDS` | API response cache TTL | `1800` |
| `VAT_RATE` | VAT rate (0.24 = 24%) | `0.24` |
| `NETWORK_FEE_EUR_PER_MWH` | Network fee | `14.50` |
| `RECIPIENT_EMAIL` | Result recipient | – |
| `GITHUB_REPO_URL` | Repo link included in the email | – |

Mail is configured via the standard `MAIL_*` variables. For local testing
without SMTP, set `MAIL_MAILER=log` (the email is written to
`storage/logs/laravel.log`). Never commit real credentials — only
`.env.example` is tracked.

## Tests & quality

```bash
php artisan test    # PHPUnit tests
vendor/bin/phpstan  # static analysis, level 9
```
