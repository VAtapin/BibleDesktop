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

Проект уже является рабочим Laravel/Vue приложением с Filament-админкой, Telegram Bot webhook и API. Основной reader поддерживает переводы Библии, вкладки чтения, параллельный перевод, Strong-номера, параллельные места, поиск, закладки, историю, заметки, календарь, молитвы, рецепты, тесты и 360° туры.

### Реализованные крупные блоки

* Bible reader: переводы из BibleQuote-модулей, выбор книги/главы, параллельный перевод, вкладки.
* Strong: номера отображаются по кнопке S#, клик открывает словарную информацию через API.
* Поиск: быстрый и расширенный поиск по стихам, результаты открывают нужную главу и стих.
* Календарь: события из `MemoryDays.xml`, чтения дня из `calendar_readings`, памятные даты 2026, постные события и правила трапезы.
* Молитвы: таблица `prayers` и части длинных молитв в `prayer_sections`; список в боковой панели, текст открывается в основном окне.
* Рецепты: категории, рецепты, структурированные ингредиенты с пересчётом порций, шаги с картинками, модерация пользовательских рецептов.
* Тесты: квизы, вопросы с картинками, разные типы ответов, ответы внутри формы вопроса, рекомендации молитвы/отрывка/текста по ответам.
* 360° туры: карточки туров в боковой панели, выбранный тур открывается в основном окне через iframe.
* Богослужения монастыря: импорт расписания из Google Calendar ICS в `monastery_services`, вывод в календаре дня и Telegram `/today`.
* Полезные материалы: управляемые ссылки на приложения, проекты и ресурсы в админке, на сайте и в Telegram.
* Telegram Bot: `/start`, `/help`, `/random`, `/search`, `/today`, `/gospel`, `/apostle`, `/fasting`, `/prayers`, `/settings`, `/ask`.
* Admin: модули Библии, страницы Footer, пользователи, Telegram-диалоги/рассылки, типы и события календаря, богослужения, молитвы, материалы, рецепты, тесты, 360° туры.

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
* `docs/LEGACY_MODULE_DECISIONS.md` - решения по legacy-модулям, которые не являются обычными переводами.
* `docs/INFRASTRUCTURE.md` - Docker/PostgreSQL/Redis окружение.

## Исходные материалы

```text
OLD/  старый Zend/PHP/jQuery проект для анализа данных и логики
UI/   макеты и визуальные материалы нового интерфейса
```

Старый проект используется только как источник данных и функциональный референс. Перенос старой архитектуры запрещён.

## Планируемый стек

```text
PHP 8.4+
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
.\tools\artisan.ps1 bible:legacy:seed-canonical-overrides
.\tools\artisan.ps1 bible:legacy:import-metadata
.\tools\artisan.ps1 bible:legacy:import-verses --library=1
.\tools\artisan.ps1 bible:legacy:import-verses --all --missing-only
.\tools\artisan.ps1 bible:legacy:import-supplemental-texts
.\tools\artisan.ps1 bible:legacy:report-skipped-verses
.\tools\artisan.ps1 bible:legacy:import-strong
.\tools\artisan.ps1 bible:legacy:import-strong-tokens --translation=L1_RST
.\tools\artisan.ps1 bible:legacy:import-cross-references
.\tools\artisan.ps1 calendar:legacy:import-events
.\tools\artisan.ps1 calendar:import-readings --path=storage/app/calendar-readings.csv
```

Canonical override seeder заполняет известные правила для Baruch/Sirach/Joel/Psalms/Esther/chapter 0 legacy cases; запускайте его до metadata import или повторите metadata import после seeding. Первичный metadata importer переносит из `OLD/bible-desktop.sql`: `library`, `book`, `chapter`, применяя безопасные `map_chapter` overrides. Verse importer переносит стихи одной legacy-библиотеки в `verses`, `verse_texts`, `legacy_verses`; по умолчанию используется RST `--library=1`, а режим `--all --missing-only` догружает все mapped legacy libraries без повторной записи уже импортированных стихов. Для 4-главного Joel (`L3_UKR`, `L5_LB`, `L325_UKH`, `L359_SCH2000NEU`) seeder добавляет verse-level rules: `Joel 3:1-5 -> Joel 2:28-32`, `Joel 4:1-21 -> Joel 3:1-21`; если эти библиотеки уже импортированы раньше, их нужно повторно прогнать без `--missing-only`. Supplemental importer переносит heading/appendix/non-canonical/requires_book_mapping legacy тексты в `legacy_supplemental_texts`, не смешивая их с canonical verses; конфликтный `L3_UKR 2Thess 4` сохраняется именно так, чтобы не перезаписать нормальную `1Tim 1`. Skipped report показывает legacy verses, которые нельзя импортировать из-за отсутствующих canonical mappings или сознательно классифицированных overrides. Strong importers переносят словари и извлекают Strong-маркеры из `verse_texts.text_raw` в `verse_strong_tokens`. Cross reference importer переносит `quote.tsk` в `cross_references`.
Calendar importer переносит события православного календаря из `OLD/MemoryDays.xml` в `calendar_event_types` и `calendar_events`; фиксированные даты, даты относительно Пасхи и постные события `legacy_type=10` доступны через общий API. Таблица `calendar_readings` хранит ручные или импортированные чтения дня: `fixed` и `pascha_relative` правила, типы `gospel`/`apostle` и ссылку на отрывок. CSV importer `calendar:import-readings` принимает UTF-8 файл с колонками `date_rule_type,month,day,offset,reading_type,title,passage_ref,sort_order`.

Памятные даты 2026 добавлены как `calendar_events.date_rule_type = fixed_year`, поэтому они привязаны к конкретному году и не повторяются автоматически в 2027+. Источник: `https://azbyka.ru/days/p-pamjatnye-daty-2026`. Постные правила добавлены отдельным типом `fasting_rule`; в `metadata_json` хранится `fasting_rule` и `meal_note` по материалу `https://azbyka.ru/days/p-kalendar-postov-i-trapez`.

Расписание богослужений монастыря импортируется из Google Calendar ICS:

```powershell
$env:MONASTERY_SERVICES_ICS_URL="https://calendar.google.com/calendar/ical/.../basic.ics"
.\tools\artisan.ps1 calendar:import-monastery-services
```

Команда разворачивает обычные и еженедельные `RRULE:FREQ=WEEKLY` события в окно от минус 3 месяцев до плюс 18 месяцев. На сервере приватную ICS-ссылку хранить только в `.env`, не в Git.

Frontend:

```powershell
npm install
npm run typecheck
npm run build
```

Docker services:

```powershell
Copy-Item .env.docker.example .env.docker
docker compose --env-file .env.docker run --rm app php artisan key:generate --show
# paste generated key into APP_KEY in .env.docker
docker compose --env-file .env.docker up -d --build
docker compose --env-file .env.docker exec app php artisan migrate --seed
```

Compose поднимает Laravel app container на PHP 8.4, queue worker, PostgreSQL 16 + pgvector и Redis. Для локального импорта `./OLD` монтируется в контейнер как read-only и не попадает в Git/Docker image.

Минимальный Docker import для рабочего стенда:

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

Локальный Docker стенд проверен с `L1_RST`, `L2_MDR`, Strong, cross references, Orthodox calendar и Telegram webhook actions. Локальные Windows helper-скрипты `tools/*.ps1` остаются удобным путём для разработки без Docker.

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
/admin/calendar-events
/admin/calendar-event-types
/admin/monastery-services
/admin/prayers
/admin/useful-links
/admin/recipe-categories
/admin/recipes
/admin/quizzes
/admin/quiz-questions
/admin/virtual-tours
/admin/pages
/admin/users
/admin/telegram-messages
/admin/telegram-broadcasts
/admin/legacy-libraries
/admin/legacy-books
/admin/legacy-chapters
/admin/legacy-verses
```

Длинные молитвы редактируются в `/admin/prayers`: поле `Вступительный текст` используется для короткой карточки в Telegram и списка на сайте, основное поле `Текст молитвы` хранит короткий или резервный полный текст, а блок `Части / страницы молитвы` хранит страницы в `prayer_sections`. На сайте список молитв остается компактным, выбранная молитва открывается в основном окне, а части показываются отдельными переключателями и подгружаются по мере выбора. В Telegram выбранная молитва сначала показывает вступление и кнопку `Читать всю`; полный текст отправляется несколькими сообщениями с преобразованием HTML в читаемые абзацы.

Доступные API endpoints:

```text
GET /api/languages
GET /api/translations
GET /api/canons/{canon}/books
GET /api/translations/{translationCode}/books
GET /api/translations/{translationCode}/supplemental-texts?book={bookSlug}&type={heading|appendix|non_canonical|requires_book_mapping}
GET /api/translations/{translationCode}/books/{bookSlug}/chapters/{chapter}
GET /api/search/verses?q={query-or-reference}&translation={translationCode}
GET /api/calendar/day?date=YYYY-MM-DD
GET /api/prayers
GET /api/prayers/{prayer}
GET /api/prayers/{prayer}/sections/{section}
GET /api/useful-links
GET /api/recipe-categories
GET /api/recipes
GET /api/recipes/{recipe}
POST /api/recipes
GET /api/quizzes
GET /api/quizzes/{quiz}
GET /api/virtual-tours
GET /api/strong/{number}
GET /api/verses/{verse}/strong-tokens
GET /api/verses/{verse}/cross-references
GET /api/verses/{verse}/notes
POST /api/verses/{verse}/notes
POST /api/telegram/webhook
```

## Сервер после обновления из Git

На Plesk/Ubuntu:

```sh
cd /var/www/vhosts/bible-desktop.com/httpdocs
git pull
/opt/plesk/php/8.4/bin/php composer.phar install --no-dev --optimize-autoloader
/opt/plesk/node/24/bin/npm install
/opt/plesk/node/24/bin/npm run build
/opt/plesk/php/8.4/bin/php artisan migrate --force
/opt/plesk/php/8.4/bin/php artisan calendar:import-monastery-services
/opt/plesk/php/8.4/bin/php artisan optimize:clear
```

Перед импортом богослужений добавить в `.env`:

```text
MONASTERY_SERVICES_ICS_URL=https://calendar.google.com/calendar/ical/.../basic.ics
MONASTERY_SERVICES_TIMEZONE=Europe/Berlin
```

Для актуального расписания можно поставить cron раз в час или раз в день:

```sh
0 * * * * cd /var/www/vhosts/bible-desktop.com/httpdocs && /opt/plesk/php/8.4/bin/php artisan calendar:import-monastery-services --quiet
```

Если Composer доступен как `composer`, можно заменить строку `php composer.phar` на обычный `composer`. После миграции новые разделы появятся в `/admin`.

Telegram Bot skeleton:

```text
TELEGRAM_BOT_TOKEN=
TELEGRAM_WEBHOOK_SECRET=
TELEGRAM_DEFAULT_TRANSLATION=L1_RST
TELEGRAM_API_BASE_URL=https://api.telegram.org
TELEGRAM_SEND_RESPONSES=false
```

Webhook endpoint validates `X-Telegram-Bot-Api-Secret-Token` when `TELEGRAM_WEBHOOK_SECRET` is set and returns actions for `/start`, `/help`, `/random`, `/search`, `/settings`, `/materials`; `/search` uses the shared verse search service and accepts text or references such as `Gen.1.1`; `/today` and `/calendar` read imported calendar events, monastery services and reading buttons, `/fasting` reads fasting events, `/gospel` and `/apostle` read `calendar_readings` and report a clear fallback when readings for the day are not set yet.

Search API uses PostgreSQL Full Text Search when the DB driver is `pgsql` and keeps the existing SQLite/LIKE fallback for local tests. Text results include `snippet_segments` for safe UI highlighting without rendering raw HTML.

To send messages from the webhook, set `TELEGRAM_SEND_RESPONSES=true`. Register webhook:

```powershell
.\tools\artisan.ps1 telegram:set-webhook https://example.com/api/telegram/webhook --drop-pending
```

## Ближайший фокус

1. Подключить или подготовить реальный CSV-источник для `calendar_readings`: отдельные Евангелие и Апостол.
2. Расширить заметки: auth user binding, редактирование, удаление, теги.
