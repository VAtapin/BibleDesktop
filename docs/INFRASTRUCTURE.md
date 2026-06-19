# Infrastructure

Дата: 2026-06-19

## Docker services

Первый Docker слой поднимает только внешние сервисы приложения:

```text
PostgreSQL 16 + pgvector
Redis 7
```

PHP/Laravel пока запускается локально через Windows helper-скрипты из `tools/`.

## Запуск

```powershell
Copy-Item .env.docker.example .env.docker
docker compose --env-file .env.docker up -d
```

После запуска PostgreSQL используйте настройки:

```text
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=bible_desktop
DB_USERNAME=bible_desktop
DB_PASSWORD=bible_desktop
```

Redis:

```text
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

## Проверка

```powershell
docker compose --env-file .env.docker ps
.\tools\artisan.ps1 migrate --seed
```

## Следующие шаги

* добавить PHP application container;
* добавить queue worker;
* добавить scheduler;
* подготовить production-like compose profile;
* включить PostgreSQL extensions migration для `vector`.
