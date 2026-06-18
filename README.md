# Bible Desktop 2.0

Современная многоязычная платформа для глубокого изучения Библии.

Проект должен объединять:

* Web Application;
* REST API;
* Telegram Bot;
* Telegram Mini App;
* Orthodox Calendar;
* AI Bible Assistant;
* Semantic Search;
* Strong Numbers;
* Cross References;
* Notes & Bookmarks;
* Group Bible Study.

## Текущее состояние

Проект находится на раннем этапе реализации: создана документация, Laravel 12 skeleton, Vue 3/TypeScript frontend shell, Filament admin panel, первые миграции модели данных, seeders базового канона и первичный legacy metadata importer.

Основные документы:

* `PROJECT_START.md` - исходное техническое задание;
* `PROJECT_SPECIFICATION.md` - рабочая копия утверждённого технического задания;
* `PROJECT_PLAN.md` - рабочий план проекта;
* `CHANGELOG.md` - журнал изменений;
* `docs/LEGACY_ANALYSIS.md` - анализ старого проекта и базы;
* `docs/UI_ANALYSIS.md` - анализ UI-макетов;
* `docs/DATA_MODEL.md` - черновик новой ER-модели;
* `docs/MIGRATION_PLAN.md` - черновик стратегии миграции;
* `docs/CALENDAR_ANALYSIS.md` - первичный анализ православного календаря.

## Исходные материалы

```text
OLD/  старый Zend/PHP/jQuery проект для анализа данных и логики
UI/   макеты и визуальные материалы нового интерфейса
```

Старый проект используется только как источник данных и функциональный референс. Перенос старой архитектуры запрещён.

## Планируемый стек

```text
PHP 8.3+
Laravel 12
Vue 3
TypeScript
Vite
Filament
PostgreSQL
pgvector
Redis
OpenAI
Telegram Bot API
Telegram Mini App
```

## Локальный запуск

Текущая PHP-сборка на рабочей машине имеет нужные расширения, но они не включены в `php.ini`. Для команд PHP используйте helper-скрипты:

```powershell
.\tools\php.ps1 artisan --version
.\tools\artisan.ps1 test
.\tools\composer.ps1 validate --no-check-publish
```

База и импорт:

```powershell
.\tools\artisan.ps1 migrate --seed
.\tools\artisan.ps1 bible:legacy:import-metadata
.\tools\artisan.ps1 bible:legacy:import-verses --library=1
```

Первичный metadata importer переносит из `OLD/bible-desktop.sql`: `library`, `book`, `chapter`. Verse importer переносит стихи одной legacy-библиотеки в `verses`, `verse_texts`, `legacy_verses`; по умолчанию используется RST `--library=1`.

Frontend:

```powershell
npm install
npm run typecheck
npm run build
```

Проверенные команды:

```powershell
npm run typecheck
npm run build
.\tools\php.ps1 vendor\bin\phpunit
.\tools\artisan.ps1 route:list
```

## Ближайший фокус

1. Подготовить импорт `quote` и Strong tokens.
2. Расширить Filament для переводов и legacy mapping.
3. Подготовить полноценный reader flow: выбор перевода, книги и главы.
4. Подготовить импорт остальных поддерживаемых переводов.
5. Подготовить Telegram Bot MVP skeleton.
