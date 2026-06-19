# Infrastructure

Дата: 2026-06-19

## Docker services

Docker compose поднимает:

```text
Laravel app container
Laravel queue worker
PostgreSQL 16 + pgvector
Redis 7
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

## Следующие шаги

* добавить scheduler;
* подготовить production-like compose profile;
* включить PostgreSQL extensions migration для `vector`.
