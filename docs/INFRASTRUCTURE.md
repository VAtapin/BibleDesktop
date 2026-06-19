# Infrastructure

Дата: 2026-06-19

## Docker services

Docker compose поднимает:

```text
Laravel app container на PHP 8.4
Laravel queue worker
PostgreSQL 16 + pgvector
Redis 7
```

`OLD/` не входит в Git и Docker image. Для локального импорта compose монтирует `./OLD`
в контейнер как read-only:

```text
./OLD -> /var/www/html/OLD:ro
```

## Запуск

```powershell
Copy-Item .env.docker.example .env.docker
docker compose --env-file .env.docker run --rm app php artisan key:generate --show
# paste generated key into APP_KEY in .env.docker
docker compose --env-file .env.docker up -d --build
```

Внутри Docker app/queue используют настройки:

```text
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=bible_desktop
DB_USERNAME=bible_desktop
DB_PASSWORD=bible_desktop
```

Redis:

```text
REDIS_HOST=redis
REDIS_PORT=6379
QUEUE_CONNECTION=redis
CACHE_STORE=redis
```

Для подключения с хоста используйте `127.0.0.1` и опубликованные порты из `.env.docker`.

## Проверка

```powershell
docker compose --env-file .env.docker ps
docker compose --env-file .env.docker exec app php artisan migrate --seed
docker compose --env-file .env.docker exec app php artisan route:list --path=api
```

## Импорт данных для рабочего стенда

Минимальный рабочий набор для reader/search/calendar/Telegram:

```powershell
docker compose --env-file .env.docker exec app php artisan bible:legacy:seed-canonical-overrides
docker compose --env-file .env.docker exec app php artisan bible:legacy:import-metadata
docker compose --env-file .env.docker exec app php artisan bible:legacy:import-verses --library=1 --missing-only
docker compose --env-file .env.docker exec app php artisan bible:legacy:import-verses --library=2 --missing-only
docker compose --env-file .env.docker exec app php artisan bible:legacy:import-supplemental-texts
docker compose --env-file .env.docker exec app php artisan bible:legacy:import-strong
docker compose --env-file .env.docker exec app php artisan bible:legacy:import-strong-tokens --translation=L1_RST
docker compose --env-file .env.docker exec app php artisan bible:legacy:import-cross-references
docker compose --env-file .env.docker exec app php artisan calendar:legacy:import-events
```

Проверенные локальные объёмы после импорта:

```text
L1_RST: 37050 verse_texts
L2_MDR: 30874 verse_texts
Strong entries: 14696
Strong tokens: 458984
Cross references: 540781
Calendar events: 3811
```

## Telegram Bot

Для локальной проверки webhook возвращает `sendMessage` actions и ничего не отправляет в Telegram:

```text
TELEGRAM_SEND_RESPONSES=false
```

Для серверного стенда нужны:

```text
TELEGRAM_BOT_TOKEN=real_token
TELEGRAM_WEBHOOK_SECRET=random_secret
TELEGRAM_SEND_RESPONSES=true
APP_URL=https://your-domain.example
```

После деплоя и HTTPS:

```powershell
docker compose --env-file .env.docker exec app php artisan telegram:set-webhook https://your-domain.example/api/telegram/webhook --drop-pending
```

## Server staging

Для первого серверного стенда лучше использовать тот же Docker compose:

1. Установить Docker и Docker Compose plugin.
2. Клонировать репозиторий.
3. Создать `.env.docker` из `.env.docker.example`.
4. Указать `APP_ENV=production`, `APP_DEBUG=false`, реальный `APP_URL`, DB password и Telegram переменные.
5. Скопировать `OLD/` на сервер рядом с `docker-compose.yml` или восстановить заранее подготовленный PostgreSQL dump.
6. Выполнить `docker compose --env-file .env.docker up -d --build`.
7. Выполнить миграции и импорт данных.
8. Поставить Nginx/Caddy перед `APP_PORT` и включить HTTPS.

## Следующие шаги

* добавить scheduler;
* подготовить production-like compose profile;
* включить PostgreSQL extensions migration для `vector`.
