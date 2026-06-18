# CHANGELOG.md

# Журнал изменений

## 2026-06-19

Задача: подключить Filament, seeders, API справочников и первичный legacy metadata importer.

Изменённые файлы:

```text
app/Console/Commands/ImportLegacyMetadata.php
app/Filament/Resources/*
app/Http/Controllers/Api/ReferenceDataController.php
app/Models/*
app/Support/LegacySqlDump.php
bootstrap/app.php
database/seeders/*
resources/js/components/BibleDesktopApp.vue
resources/css/app.css
routes/api.php
tests/*
README.md
PROJECT_PLAN.md
CHANGELOG.md
```

Описание изменений:

* установлен Filament 5.6 и создана admin panel `/admin`;
* добавлены Filament resources для языков, канонов и канонических книг;
* добавлены seeders для 5 языков и 77-книжного православного канона;
* добавлены API endpoints `GET /api/languages` и `GET /api/canons/{canon}/books`;
* Vue reader shell подключён к API справочников;
* добавлен SQL dump reader и команда `bible:legacy:import-metadata`;
* importer переносит legacy `library`, `book`, `chapter` в `modules`, `translations`, `module_books`, `module_chapters` и `legacy_*`;
* добавлены тесты seeders, API и SQL dump reader;
* `.gitignore` дополнен локальными SQLite/test артефактами.

Результат:

* `route:list` видит `/admin/*` и `/api/*`;
* на реальном `OLD/bible-desktop.sql` importer переносит 21 поддерживаемую Bible-библиотеку, 1205 книг и 20773 главы;
* проверки проходят: PHPUnit 6 tests / 24 assertions, `npm run typecheck`, `npm run build`, `composer validate`.

---

## 2026-06-18

Задача: создать Laravel/Vue foundation и первые миграции.

Изменённые файлы:

```text
.gitignore
.env.example
composer.json
composer.lock
package.json
package-lock.json
routes/web.php
vite.config.js
resources/css/app.css
resources/js/*
resources/views/app.blade.php
database/migrations/*
tools/*.ps1
README.md
PROJECT_PLAN.md
CHANGELOG.md
```

Описание изменений:

* создан Laravel 12 skeleton в корне проекта;
* подключены Vue 3 и TypeScript;
* стартовый экран заменён на Bible Desktop reader shell;
* добавлены первые миграции Bible core, modules/translations, Strong, cross references, legacy mappings, calendar, notes/bookmarks/tags;
* `.env.example` переключён на PostgreSQL;
* добавлены Windows helper-скрипты для запуска PHP с нужными расширениями;
* обновлены README и рабочий план.

Результат:

* приложение загружается как Laravel 12;
* frontend проходит `npm run typecheck` и `npm run build`;
* миграции проходят на SQLite memory;
* PHPUnit проходит: 2 теста, 2 assertions.

---

## 2026-06-18

Задача: изучить источник православного календаря.

Изменённые файлы:

```text
README.md
PROJECT_PLAN.md
CHANGELOG.md
docs/CALENDAR_ANALYSIS.md
```

Описание изменений:

* изучен `OLD/MemoryDays.xml`;
* зафиксирована структура календарного события;
* описана предварительная интерпретация фиксированных и подвижных дат;
* предложена новая модель календарных событий;
* обновлены ближайшие задачи.

Результат:

* календарный источник понятен на уровне первичного импорта, но расшифровка всех `type` значений остаётся отдельной задачей.

---

## 2026-06-18

Задача: спроектировать черновик новой модели данных и миграции.

Изменённые файлы:

```text
PROJECT_PLAN.md
README.md
CHANGELOG.md
docs/DATA_MODEL.md
docs/MIGRATION_PLAN.md
```

Описание изменений:

* описана новая ER-модель с разделением канонического стиха и текста перевода;
* описаны таблицы модулей, переводов, книг, глав, стихов, Strong, cross references и legacy mapping;
* описан порядок импорта из старой MariaDB базы;
* описаны черновые правила парсинга Strong-маркеров и `quote.tsk`;
* обновлены ближайшие задачи проекта.

Результат:

* есть техническая основа для создания Laravel migrations и импорт-команд.

---

## 2026-06-18

Задача: начать этап анализа старого проекта и UI.

Изменённые файлы:

```text
PROJECT_SPECIFICATION.md
PROJECT_PLAN.md
README.md
CHANGELOG.md
docs/LEGACY_ANALYSIS.md
docs/UI_ANALYSIS.md
```

Описание изменений:

* создан `PROJECT_SPECIFICATION.md` как отдельная рабочая копия утверждённого ТЗ;
* проведён первичный анализ `OLD/bible-desktop.sql`;
* зафиксированы ключевые таблицы старой базы, примерные объёмы данных и карта миграции;
* проведён первичный анализ `UI/bible-desktop_v2.jpg`;
* обновлён рабочий план: этап 0 почти завершён, этап 1 переведён в работу.

Результат:

* есть документированная база для проектирования новой ER-модели и миграций.

---

## 2026-06-18

Задача: исключить старый проект `OLD/` из Git и подготовить коммит.

Изменённые файлы:

```text
.gitignore
CHANGELOG.md
```

Описание изменений:

* создан `.gitignore`;
* добавлено правило `/OLD/`, чтобы старый Zend/PHP/jQuery проект оставался только локальным источником анализа;
* добавлены базовые правила игнорирования для окружения, зависимостей, сборки, кеша и IDE.

Результат:

* папка `OLD/` больше не должна попадать в Git.

---

## 2026-06-18

Задача: создать стартовый рабочий план проекта.

Изменённые файлы:

```text
PROJECT_PLAN.md
README.md
CHANGELOG.md
```

Описание изменений:

* создан `PROJECT_PLAN.md` на основе `PROJECT_START.md`;
* зафиксированы этапы реализации, MVP, версия 2.0, открытые вопросы и предложения по корректировке ТЗ;
* расширен `README.md`;
* создан `CHANGELOG.md`.

Результат:

* у проекта появился рабочий план, чтобы вести разработку последовательно и не потеряться в большом объёме задач.
