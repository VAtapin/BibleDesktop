# CHANGELOG.md

# Журнал изменений

## 2026-06-19

Задача: подключить Filament, seeders, API справочников и первичные legacy importers.

Изменённые файлы:

```text
app/Console/Commands/ImportLegacyMetadata.php
app/Console/Commands/ImportLegacyVerses.php
app/Console/Commands/ImportLegacyStrong.php
app/Console/Commands/ImportLegacyStrongTokens.php
app/Console/Commands/ImportLegacyCrossReferences.php
app/Filament/Resources/*
app/Http/Controllers/Api/ReferenceDataController.php
app/Http/Controllers/Api/ChapterController.php
app/Http/Controllers/Api/StudyDataController.php
app/Models/*
app/Support/LegacySqlDump.php
app/Support/TskReferenceParser.php
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
* добавлены Filament resources для модулей, переводов и read-only legacy mapping таблиц;
* добавлены seeders для 5 языков и 77-книжного православного канона;
* добавлены API endpoints `GET /api/languages` и `GET /api/canons/{canon}/books`;
* добавлен API endpoint `GET /api/translations`;
* добавлен API endpoint `GET /api/translations/{translationCode}/books/{bookSlug}/chapters/{chapter}`;
* добавлены Study API endpoints `GET /api/strong/{number}`, `GET /api/verses/{verse}/strong-tokens`, `GET /api/verses/{verse}/cross-references`;
* Vue reader shell подключён к API справочников;
* Vue reader shell подключён к chapter endpoint с fallback на demo-текст;
* Vue reader shell получил рабочий выбор перевода, книги, главы и кнопки перехода между главами;
* Vue reader shell показывает Strong tokens и cross references выбранного стиха в правой панели;
* добавлен SQL dump reader и команда `bible:legacy:import-metadata`;
* importer переносит legacy `library`, `book`, `chapter` в `modules`, `translations`, `module_books`, `module_chapters` и `legacy_*`;
* metadata importer исключает legacy псевдомодуль `Greek letters / GLNT` и чистит устаревшие legacy mappings без verse texts;
* добавлена команда `bible:legacy:import-verses --library=1`;
* команда `bible:legacy:import-verses` получила режимы `--all` и `--missing-only` для импорта/допрогона всех поддерживаемых legacy переводов;
* verse importer переносит RST в `verses`, `verse_texts`, `legacy_verses`, очищая Strong-коды из display-текста и сохраняя исходник в `text_raw`;
* добавлена команда `bible:legacy:import-strong` для `strong_lexicons` и `strong_numbers`;
* добавлена команда `bible:legacy:import-strong-tokens --translation=L1_RST` для переноса Strong-маркеров из `verse_texts.text_raw` в `verse_strong_tokens`;
* добавлена команда `bible:legacy:import-cross-references` и TSK parser для переноса `quote.tsk` в `cross_references`;
* добавлены тесты seeders, API, SQL dump reader и TSK parser;
* `.gitignore` дополнен локальными SQLite/test артефактами.

Результат:

* `route:list` видит `/admin/*`, включая `/admin/bible-modules`, `/admin/translations`, `/admin/legacy-*`, и `/api/*`;
* на реальном `OLD/bible-desktop.sql` metadata importer переносит 20 поддерживаемых Bible-библиотек, 1204 книги и 20773 главы;
* RST verse importer переносит 37050 verse texts; `Gen.1.1` сохраняется как `В начале сотворил Бог небо и землю.`, а Strong-разметка остаётся в `text_raw`;
* all-verses importer перенёс 540527 verse texts по 20 mapped legacy libraries; повторный `--missing-only` добавляет 0 строк, 540527 уже импортированы, 1127 skipped;
* Strong importer переносит 2 лексикона и 14696 словарных статей; `H7225` проверен как Hebrew entry;
* Strong token importer для `L1_RST` просканировал 31160 стихов с разметкой и перенёс 458984 токена в `verse_strong_tokens`;
* cross reference importer просканировал 31092 legacy quotes и сохранил 540781 связь; `Gen.1.1` имеет 72 cross references;
* Study API проверен тестами и на реальной testing.sqlite: `Gen.1.1` имеет 8 Strong tokens и 72 cross references;
* проверки проходят: PHPUnit 11 tests / 53 assertions, `npm run typecheck`, `npm run build`, `composer validate`.

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
