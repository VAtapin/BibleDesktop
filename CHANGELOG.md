# CHANGELOG.md

# Журнал изменений

## 2026-06-19

Задача: добавить CSV importer для чтений дня.

Изменённые файлы:

```text
app/Console/Commands/ImportCalendarReadings.php
tests/Feature/ImportCalendarReadingsCommandTest.php
README.md
PROJECT_PLAN.md
CHANGELOG.md
```

Описание изменений:

* добавлена команда `calendar:import-readings`;
* команда импортирует UTF-8 CSV с fixed и pascha-relative правилами;
* поддержаны типы `gospel` и `apostle`;
* добавлен feature test, который проверяет import и выдачу через Calendar API.

Результат:

* для `calendar_readings` появился нормальный канал наполнения из внешнего или ручного источника.

---

Задача: сохранить конфликтный duplicate book mapping как supplemental text.

Изменённые файлы:

```text
app/Console/Commands/ImportLegacySupplementalTexts.php
tests/Feature/ImportLegacySupplementalTextsCommandTest.php
docs/DATA_MODEL.md
docs/LEGACY_MODULE_DECISIONS.md
README.md
PROJECT_PLAN.md
CHANGELOG.md
```

Описание изменений:

* `bible:legacy:import-supplemental-texts` по умолчанию импортирует `requires_book_mapping`;
* конфликтный `L3_UKR 2Thess 4 -> 1Tim 1` сохраняется в `legacy_supplemental_texts`;
* canonical `verse_texts` не перезаписывается, поэтому нормальная `1Tim 1` остаётся основной;
* добавлен feature test на импорт `requires_book_mapping`.

Результат:

* duplicate mapping cases больше не теряются, но не смешиваются с canonical verses.
* на `database/testing.sqlite` supplemental counts: 65 appendix, 27 heading, 20 requires_book_mapping.

---

Задача: добавить verse-level canonical overrides для 4-главного Joel.

Изменённые файлы:

```text
database/migrations/2026_06_19_001000_create_legacy_canonical_verse_overrides_table.php
app/Console/Commands/SeedLegacyCanonicalOverrides.php
app/Console/Commands/ImportLegacyVerses.php
app/Console/Commands/ReportSkippedLegacyVerses.php
tests/Feature/SeedLegacyCanonicalOverridesCommandTest.php
tests/Feature/ImportLegacyVersesCommandTest.php
tests/Feature/ReportSkippedLegacyVersesCommandTest.php
docs/DATA_MODEL.md
docs/LEGACY_MODULE_DECISIONS.md
README.md
PROJECT_PLAN.md
CHANGELOG.md
```

Описание изменений:

* добавлена таблица `legacy_canonical_verse_overrides`;
* seeder создаёт 104 правила для 4-главного Joel;
* `bible:legacy:import-verses` применяет `map_verse` до chapter mapping;
* `bible:legacy:report-skipped-verses` больше не считает стихи с verse override как skipped;
* добавлены feature tests для seeding, verse import и skipped report.

Результат:

* legacy `Joel 3:1-5` переносится как canonical `Joel 2:28-32`;
* legacy `Joel 4:1-21` переносится как canonical `Joel 3:1-21`;
* на `database/testing.sqlite` skipped report снизился до 112 строк: 65 appendix, 27 heading, 20 конфликтных `requires_book_mapping`;
* переимпорт `L3_UKR`, `L5_LB`, `L325_UKH`, `L359_SCH2000NEU` подтвердил mapping для `Joel.2.28` и `Joel.3.1`;
* конфликтный `L3_UKR 2Thess 4 -> 1Tim 1` оставлен отдельной задачей, чтобы не перезаписать нормальную `1Tim 1`.

---

Задача: добавить ручную модель чтений дня для календаря и Telegram.

Изменённые файлы:

```text
database/migrations/2026_06_19_000900_create_calendar_readings_table.php
app/Services/Calendar/OrthodoxCalendarService.php
app/Services/Telegram/TelegramUpdateHandler.php
tests/Feature/CalendarApiTest.php
tests/Feature/TelegramWebhookTest.php
README.md
PROJECT_PLAN.md
CHANGELOG.md
```

Описание изменений:

* добавлена таблица `calendar_readings` для fixed и pascha-relative чтений;
* `OrthodoxCalendarService::day()` теперь возвращает `readings`;
* Telegram `/gospel` и `/apostle` читают реальные записи из `calendar_readings`;
* если чтение не задано, бот возвращает короткий понятный fallback;
* добавлены feature tests для Calendar API и Telegram gospel command.

Результат:

* календарь получил import-ready слой для Евангелия и Апостола дня без зависимости от legacy SQL, где отдельного источника чтений нет.

---

Задача: открыть supplemental legacy texts через API.

Изменённые файлы:

```text
app/Http/Controllers/Api/SupplementalTextController.php
routes/api.php
tests/Feature/SupplementalTextApiTest.php
README.md
PROJECT_PLAN.md
CHANGELOG.md
```

Описание изменений:

* добавлен endpoint `GET /api/translations/{translation}/supplemental-texts`;
* endpoint поддерживает фильтры `book`, `type`, `limit`;
* response возвращает translation metadata и список supplemental items;
* добавлен feature test на фильтрацию по книге и типу.

Результат:

* heading/appendix материалы стали доступны через REST API;
* `route:list --path=api` показывает 12 API routes.

---

Задача: импортировать supplemental legacy texts отдельно от canonical verses.

Изменённые файлы:

```text
database/migrations/2026_06_19_000800_create_legacy_supplemental_texts_table.php
app/Console/Commands/ImportLegacySupplementalTexts.php
tests/Feature/ImportLegacySupplementalTextsCommandTest.php
README.md
PROJECT_PLAN.md
docs/DATA_MODEL.md
docs/LEGACY_MODULE_DECISIONS.md
CHANGELOG.md
```

Описание изменений:

* добавлена таблица `legacy_supplemental_texts`;
* добавлена команда `bible:legacy:import-supplemental-texts`;
* команда импортирует override actions `heading`, `appendix`, `non_canonical` отдельно от `verse_texts`;
* добавлен feature test на heading import и idempotent upsert.

Результат:

* на `database/testing.sqlite` импортировано 92 supplemental rows: 65 appendix и 27 heading;
* appendix/heading больше не нужно превращать в canonical chapters ради сохранения текста.

---

Задача: применить target canonical chapter в verse importer.

Изменённые файлы:

```text
app/Console/Commands/ImportLegacyVerses.php
tests/Feature/ImportLegacyVersesCommandTest.php
README.md
PROJECT_PLAN.md
CHANGELOG.md
```

Описание изменений:

* `bible:legacy:import-verses` теперь читает target canonical book/chapter из `canonical_chapters`, если legacy chapter замаплен override-ом;
* single-library и all-library import используют одинаковую сборку legacy source row;
* добавлен feature test для случая `Baruch 6 -> Epistle of Jeremiah 1`;
* порядок команд в README/плане уточнён: canonical overrides seeding должен идти до metadata import или metadata import нужно повторить после seeding.

Результат:

* mapped chapters могут корректно уходить в другую canonical book и получать правильный OSIS ref, например `EpJer.1.1`;
* на `database/testing.sqlite` повторный metadata import снизил skipped report с 268 до 196, а `bible:legacy:import-verses --library=10` импортировал DRB без skipped и создал `L10_DRB EpJer.1.1`.

---

Задача: наполнить canonical mapping overrides реальными правилами.

Изменённые файлы:

```text
app/Console/Commands/SeedLegacyCanonicalOverrides.php
tests/Feature/SeedLegacyCanonicalOverridesCommandTest.php
README.md
PROJECT_PLAN.md
docs/DATA_MODEL.md
docs/LEGACY_MODULE_DECISIONS.md
CHANGELOG.md
```

Описание изменений:

* добавлена команда `bible:legacy:seed-canonical-overrides`;
* команда idempotent-но создаёт 38 правил для Baruch 6, Sirach 52, Joel 4, Psalms 151/152, Esther 11, IBSNT chapter 0 и legacy anomaly `L3_UKR 2thessalonians 4`;
* Baruch 6 классифицируется как `map_chapter` на `epistle 1`;
* Joel 4 помечен как `requires_verse_mapping`, а misaligned UKR chapter как `requires_book_mapping`;
* appendix/heading cases отделены от реальных mapping misses.

Результат:

* после seeding на `database/testing.sqlite` обычных `missing_canonical_chapter` не осталось;
* report показывает: 72 `override_map_chapter`, 84 `override_requires_verse_mapping`, 20 `override_requires_book_mapping`, 65 `override_appendix`, 27 `override_heading`.

---

Задача: добавить первый слой canonical mapping overrides.

Изменённые файлы:

```text
database/migrations/2026_06_19_000700_create_legacy_canonical_overrides_table.php
app/Console/Commands/ImportLegacyMetadata.php
app/Console/Commands/ReportSkippedLegacyVerses.php
tests/Feature/ImportLegacyMetadataCommandTest.php
tests/Feature/ReportSkippedLegacyVersesCommandTest.php
docs/LEGACY_MODULE_DECISIONS.md
docs/DATA_MODEL.md
README.md
PROJECT_PLAN.md
CHANGELOG.md
```

Описание изменений:

* добавлена таблица `legacy_canonical_chapter_overrides`;
* override может быть привязан к конкретной legacy library или быть глобальным;
* `bible:legacy:import-metadata` применяет действие `map_chapter`, если обычный canonical lookup не нашёл главу;
* `bible:legacy:report-skipped-verses` показывает override cases как `override_{action}`;
* добавлен feature test на применение chapter mapping override;
* добавлен feature test на классификацию skipped rows через override action;
* документация описывает будущие действия `heading`, `appendix`, `non_canonical`, `requires_verse_mapping`.

Результат:

* появился безопасный слой для Baruch/Sirach/Joel/Psalms/Esther/chapter 0 cases без расширения базового канона случайными главами;
* проверки проходят: `ImportLegacyMetadataCommandTest`.

---

Задача: отделить legacy commentary modules от Bible translations.

Изменённые файлы:

```text
app/Console/Commands/ImportLegacyMetadata.php
app/Http/Controllers/Api/ChapterController.php
app/Http/Controllers/Api/ReferenceDataController.php
app/Services/Bible/VerseSearchService.php
tests/Feature/ImportLegacyMetadataCommandTest.php
docs/LEGACY_MODULE_DECISIONS.md
README.md
PROJECT_PLAN.md
CHANGELOG.md
```

Описание изменений:

* `LOP` / `Толковая Библии - А.Лопухина` классифицируется как `modules.type = commentary`;
* commentary-модуль не создаёт `translations` в fresh import и не попадает в `module_books` как Bible translation;
* Bible endpoints и `VerseSearchService` фильтруют `modules.type = bible`;
* добавлен feature test для metadata importer;
* добавлен decision-документ по LOP и оставшимся canonical mapping расхождениям.

Результат:

* на текущем `database/testing.sqlite` skipped legacy verses после повторного metadata import снизились с 1127 до 268;
* оставшиеся skipped rows теперь относятся к Baruch/Sirach/Joel/Psalms/Esther/chapter 0, а не к Лопухинскому комментарию.

---

Задача: сделать настоящие вкладки чтения в reader shell.

Изменённые файлы:

```text
resources/js/components/BibleDesktopApp.vue
resources/css/app.css
README.md
PROJECT_PLAN.md
CHANGELOG.md
```

Описание изменений:

* декоративный список вкладок заменён на состояние `readerTabs`;
* каждая вкладка хранит перевод, книгу и главу;
* переключение вкладки загружает соответствующие книги, главу и справочные данные;
* можно открыть новую вкладку из текущего места чтения и закрыть лишнюю;
* открытые вкладки и активная вкладка сохраняются в `localStorage`.

Результат:

* reader shell получил рабочую модель нескольких вкладок;
* проверки проходят: `npm run typecheck`, `npm run build`.

---

Задача: переиспользовать Search API в Telegram Bot.

Изменённые файлы:

```text
app/Http/Controllers/Api/SearchController.php
app/Services/Bible/VerseSearchService.php
app/Services/Telegram/TelegramUpdateHandler.php
tests/Feature/TelegramWebhookTest.php
README.md
PROJECT_PLAN.md
CHANGELOG.md
```

Описание изменений:

* логика поиска стихов вынесена из API controller в `VerseSearchService`;
* `SearchController` оставлен тонким HTTP-слоем;
* Telegram `/search` теперь использует тот же сервис, что API и reader UI;
* Telegram `/search` принимает не только текст, но и ссылки вида `Gen.1.1`.

Результат:

* поиск больше не дублируется между API и Telegram Bot;
* проверки проходят: `SearchApiTest`, `TelegramWebhookTest`.

---

Задача: сохранить состояние reader shell.

Изменённые файлы:

```text
resources/js/components/BibleDesktopApp.vue
README.md
PROJECT_PLAN.md
CHANGELOG.md
```

Описание изменений:

* reader сохраняет выбранный перевод, книгу и главу в `localStorage`;
* при загрузке сохранённое состояние восстанавливается, если перевод и книга ещё доступны;
* активная вкладка reader показывает текущую книгу и главу вместо статичного заголовка.

Результат:

* reader не сбрасывается на стартовую главу после перезагрузки страницы;
* проверки проходят: `npm run typecheck`, `npm run build`.

---

Задача: подключить поиск в reader UI.

Изменённые файлы:

```text
resources/js/components/BibleDesktopApp.vue
resources/css/app.css
README.md
PROJECT_PLAN.md
CHANGELOG.md
```

Описание изменений:

* верхняя строка поиска reader shell вызывает `GET /api/search/verses`;
* результаты показываются компактным dropdown-списком;
* клик по результату переключает книгу, главу и выделенный стих;
* поиск поддерживает и обычный текст, и ссылки, которые уже распознаёт Search API;
* добавлено состояние пустого/ошибочного результата поиска.

Результат:

* reader получил первый рабочий переход к стиху через поиск;
* проверки проходят: `npm run typecheck`, `npm run build`, PHPUnit 22 tests / 99 assertions.

---

Задача: добавить поиск стихов по ссылке.

Изменённые файлы:

```text
app/Http/Controllers/Api/SearchController.php
tests/Feature/SearchApiTest.php
README.md
PROJECT_PLAN.md
CHANGELOG.md
```

Описание изменений:

* `GET /api/search/verses` распознаёт ссылки вида `Gen.1.1` и `Быт. 1:1`;
* поиск книги использует `canonical_books`, `canonical_book_names` и `module_books` выбранного перевода;
* response получил поле `mode`: `text` или `reference`;
* обычный DB-agnostic LIKE-поиск по `verse_texts.text_plain` сохранён без изменения.

Результат:

* Search API покрыт тестами на текстовый поиск, OSIS reference и русское краткое имя книги;
* проверки проходят: PHPUnit 22 tests / 99 assertions.

---

Задача: диагностировать skipped legacy verses.

Изменённые файлы:

```text
.gitignore
app/Console/Commands/ReportSkippedLegacyVerses.php
tests/Feature/ReportSkippedLegacyVersesCommandTest.php
README.md
PROJECT_PLAN.md
CHANGELOG.md
```

Описание изменений:

* добавлена команда `bible:legacy:report-skipped-verses`;
* команда читает legacy SQL dump и группирует skipped verse rows по причине, библиотеке, книге и legacy chapter;
* добавлен агрегат по библиотекам, чтобы отделять системные проблемы модулей от точечных canonical mapping расхождений;
* `.gitignore` дополнен локальной папкой `/.tmp/` для временных инструментов;
* добавлен feature test для сценария `missing_canonical_chapter`.

Результат:

* реальный отчёт на `database/testing.sqlite` показывает 1127 skipped verses, все с причиной `missing_canonical_chapter`;
* крупнейший источник skipped строк: `L376_LOP` с 859 строками, вероятно commentary/non-Bible module;
* заметные точечные расхождения: `Baruch 6`, `Sirach 52`, `Joel 4`;
* проверки проходят: PHPUnit 22 tests / 92 assertions.

---

Задача: улучшить reader flow для книг перевода.

Изменённые файлы:

```text
app/Http/Controllers/Api/ReferenceDataController.php
resources/js/components/BibleDesktopApp.vue
routes/api.php
tests/Feature/ReferenceDataApiTest.php
README.md
PROJECT_PLAN.md
CHANGELOG.md
```

Описание изменений:

* добавлен endpoint `GET /api/translations/{translationCode}/books`;
* endpoint возвращает книги конкретного перевода из `module_books` с реальными названиями, порядком, количеством глав и canonical metadata;
* Vue reader загружает книги после выбора перевода и обновляет список книг при смене перевода;
* reader показывает понятное состояние, если в выбранной главе ещё нет импортированного текста;
* добавлен feature test для endpoint книг перевода.

Результат:

* reader больше не зависит от английских имён канонических книг для выбора книги;
* `route:list --path=api` показывает 11 API routes;
* проверки проходят: PHPUnit 21 tests / 89 assertions, `npm run typecheck`, `npm run build`.

---

Задача: импортировать православный календарь и подключить календарные Telegram-команды.

Изменённые файлы:

```text
app/Console/Commands/ImportLegacyCalendarEvents.php
app/Http/Controllers/Api/CalendarController.php
app/Services/Calendar/OrthodoxCalendarService.php
app/Services/Telegram/TelegramUpdateHandler.php
docs/CALENDAR_ANALYSIS.md
routes/api.php
tests/Feature/CalendarApiTest.php
tests/Feature/TelegramWebhookTest.php
README.md
PROJECT_PLAN.md
CHANGELOG.md
```

Описание изменений:

* добавлена команда `calendar:legacy:import-events` для импорта `OLD/MemoryDays.xml`;
* legacy calendar importer создаёт типы событий и переносит фиксированные даты, даты относительно Пасхи и диапазоны;
* добавлен `OrthodoxCalendarService` с расчётом православной Пасхи и подбором событий дня;
* добавлен API endpoint `GET /api/calendar/day?date=YYYY-MM-DD`;
* API календаря отдельно отдаёт `fasting_events` для постных событий `legacy_type=10`;
* Telegram команды `/today` и `/calendar` подключены к импортированным событиям текущего дня;
* Telegram команда `/fasting` показывает постные события текущего дня;
* Telegram команды `/gospel` и `/apostle` возвращают честный placeholder, потому что отдельный источник чтений в legacy SQL не найден;
* добавлены feature tests для calendar API/importer и Telegram calendar command.

Результат:

* реальный импорт `OLD/MemoryDays.xml` перенёс 48 типов и 3811 календарных событий;
* `GET /api/calendar/day` покрыт тестом на фиксированную дату и Пасху 2026 года;
* постные события покрыты тестом на Великий пост;
* проверки проходят: PHPUnit 20 tests / 84 assertions.

---

Задача: подключить Filament, seeders, API справочников и первичные legacy importers.

Изменённые файлы:

```text
app/Console/Commands/ImportLegacyMetadata.php
app/Console/Commands/ImportLegacyVerses.php
app/Console/Commands/ImportLegacyStrong.php
app/Console/Commands/ImportLegacyStrongTokens.php
app/Console/Commands/ImportLegacyCrossReferences.php
app/Console/Commands/TelegramSetWebhook.php
app/Filament/Resources/*
app/Http/Controllers/Api/ReferenceDataController.php
app/Http/Controllers/Api/ChapterController.php
app/Http/Controllers/Api/StudyDataController.php
app/Http/Controllers/Api/TelegramWebhookController.php
app/Http/Controllers/Api/SearchController.php
app/Models/*
app/Services/Telegram/TelegramUpdateHandler.php
app/Services/Telegram/TelegramBotClient.php
app/Support/LegacySqlDump.php
app/Support/TskReferenceParser.php
bootstrap/app.php
docker-compose.yml
docs/INFRASTRUCTURE.md
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
* добавлен базовый Search API endpoint `GET /api/search/verses`;
* добавлен Telegram Bot MVP skeleton: config/env, webhook endpoint и handler для `/start`, `/help`, `/random`;
* Telegram Bot skeleton получил optional real sending через Telegram Bot API и команду `telegram:set-webhook`;
* Telegram Bot handler получил `/search`, `/today`, `/gospel`, `/apostle`, `/calendar`, `/fasting`, `/settings` skeleton responses;
* добавлен Docker compose для PostgreSQL 16 + pgvector и Redis;
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
* Search API проверен тестами и на реальной testing.sqlite: запрос `сотворил` по `L1_RST` возвращает `Gen.1.1`;
* проверки проходят: PHPUnit 16 tests / 67 assertions, `npm run typecheck`, `npm run build`, `composer validate`.

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
