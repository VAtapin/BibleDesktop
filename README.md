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

Проект находится на раннем этапе реализации: создана документация, Laravel 12 skeleton, Vue 3/TypeScript frontend shell, Filament admin panel, первые миграции модели данных, seeders базового канона, admin resources, первичные legacy importers и первый перенос RST/Strong/cross reference данных.

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
.\tools\artisan.ps1 bible:legacy:import-strong
.\tools\artisan.ps1 bible:legacy:import-strong-tokens --translation=L1_RST
.\tools\artisan.ps1 bible:legacy:import-cross-references
```

Первичный metadata importer переносит из `OLD/bible-desktop.sql`: `library`, `book`, `chapter`. Verse importer переносит стихи одной legacy-библиотеки в `verses`, `verse_texts`, `legacy_verses`; по умолчанию используется RST `--library=1`. Strong importers переносят словари и извлекают Strong-маркеры из `verse_texts.text_raw` в `verse_strong_tokens`. Cross reference importer переносит `quote.tsk` в `cross_references`.

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

Admin panel:

```text
/admin/languages
/admin/canons
/admin/canonical-books
/admin/bible-modules
/admin/translations
/admin/legacy-libraries
/admin/legacy-books
/admin/legacy-chapters
/admin/legacy-verses
```

Доступные API endpoints:

```text
GET /api/languages
GET /api/translations
GET /api/canons/{canon}/books
GET /api/translations/{translationCode}/books/{bookSlug}/chapters/{chapter}
```

## Ближайший фокус

1. Подготовить импорт остальных поддерживаемых переводов.
2. Подготовить API для Strong и cross references.
3. Улучшить reader flow: имена книг из module_books, состояние вкладок, обработка пустых глав.
4. Подготовить Telegram Bot MVP skeleton.
5. Подготовить Docker/PostgreSQL окружение.
