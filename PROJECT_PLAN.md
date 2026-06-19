# PROJECT_PLAN.md

# Bible Desktop 2.0 - рабочий план проекта

Версия плана: 0.3  
Дата: 2026-06-19  
Статус: создан Laravel/Vue/Filament foundation, базовый канон, API справочников/главы, admin resources, legacy metadata importer, первый импорт RST стихов, Strong-токенов и cross references

---

# 1. Назначение документа

`PROJECT_PLAN.md` - главный рабочий документ проекта. Он нужен, чтобы:

* не потерять общую архитектуру большого проекта;
* фиксировать порядок работ;
* хранить принятые решения;
* видеть текущий статус;
* отделять обязательный MVP от задач версии 2.0 и будущих идей;
* фиксировать вопросы к техническому заданию.

Техническое задание находится в `PROJECT_START.md`. По мере стабилизации его стоит вынести в отдельный `PROJECT_SPECIFICATION.md`.

---

# 2. Текущее состояние

## 2.1 Репозиторий

Локальный путь:

```text
D:\Projects\BibleDesktop
```

GitHub:

```text
https://github.com/VAtapin/BibleDesktop
```

## 2.2 Наличие исходных материалов

```text
OLD/              старый Zend/PHP/jQuery проект, источник данных и логики
UI/               макеты и визуальные материалы нового интерфейса
PROJECT_START.md  исходное техническое задание
README.md         краткое описание проекта
```

## 2.3 Важные ограничения

Старый проект используется только для анализа:

* структуры данных;
* связей таблиц;
* бизнес-логики;
* миграции данных;
* функциональных сценариев.

Запрещено переносить:

* Zend Framework;
* jQuery-архитектуру;
* старую структуру кода;
* старые модели и контроллеры как основу нового проекта.

---

# 3. Цель проекта

Создать современную многоязычную платформу для глубокого изучения Библии:

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

---

# 4. Принципы архитектуры

## 4.1 API First

Вся бизнес-логика должна быть доступна через API. Web UI, Telegram Bot и Telegram Mini App используют общий backend.

## 4.2 Модульность

Основные подсистемы:

* Bible;
* Search;
* AI;
* Calendar;
* Telegram;
* Notes;
* Groups;
* Admin;
* Migration.

## 4.3 Мультиязычность с первого дня

Нельзя проектировать одноязычную модель данных или одноязычный интерфейс. Минимальные языки:

* русский;
* украинский;
* белорусский;
* немецкий;
* английский.

## 4.4 Расширяемость

Система должна поддерживать новые языки, переводы, AI-провайдеры и типы модулей без пересборки всей архитектуры.

---

# 5. Технологический стек

## Backend

```text
PHP 8.3+
Laravel 12
Laravel Sanctum
Laravel Queue
```

## Frontend

```text
Vue 3
TypeScript
Vite
```

## Admin

```text
Filament
```

## Database

```text
PostgreSQL
pgvector
```

## Infrastructure

```text
Docker
Redis
```

## AI

```text
OpenAI
AI provider abstraction
```

## Telegram

```text
Telegram Bot API
Telegram Mini App
```

---

# 6. Предлагаемая целевая структура проекта

Структура будет уточняться после создания Laravel/Vue приложения.

```text
app/
  Domain/
    Bible/
    Search/
    Ai/
    Calendar/
    Telegram/
    Notes/
    Groups/
  Http/
  Console/
  Filament/
database/
  migrations/
  seeders/
resources/
  js/
  views/
routes/
  api.php
  web.php
tests/
docs/
```

Принцип: доменная логика должна жить отдельно от контроллеров, команд импорта и UI.

---

# 7. Рабочий процесс

Перед каждой задачей:

1. Прочитать `PROJECT_PLAN.md`.
2. Прочитать `README.md`.
3. Прочитать `CHANGELOG.md`, если он существует.
4. Проверить `git status`.
5. Сверить задачу с текущим этапом.

После каждой задачи:

1. Обновить `PROJECT_PLAN.md`, если изменился статус, решение или план.
2. Обновить `README.md`, если изменились запуск, установка или текущее состояние.
3. Обновить `CHANGELOG.md`.
4. Зафиксировать результат задачи.

---

# 8. Этапы реализации

## Этап 0. Документация и проектная рамка

Статус: почти завершён

Задачи:

* создать `PROJECT_PLAN.md` - выполнено;
* создать `CHANGELOG.md` - выполнено;
* расширить `README.md` - выполнено;
* вынести утверждённое ТЗ в `PROJECT_SPECIFICATION.md` - выполнено;
* зафиксировать спорные и открытые вопросы;
* определить минимальный MVP.

Результат:

* проект можно вести последовательно;
* есть точка контроля перед началом кода.

## Этап 1. Анализ старого проекта и UI

Статус: в работе

Задачи:

* изучить `OLD/` - начато;
* найти структуру старой базы данных - выполнено, основной дамп `OLD/bible-desktop.sql`;
* описать старые таблицы `library`, `book`, `chapter`, `verse`, `strong_numbers`, `strong_lexicons`, `quote` - первично выполнено в `docs/LEGACY_ANALYSIS.md`;
* понять старые связи и миграционную стратегию - первично выполнено в `docs/MIGRATION_PLAN.md`;
* изучить `UI/` - начато;
* описать основные экраны, панели, вкладки и контекстное меню стиха - первично выполнено в `docs/UI_ANALYSIS.md`.

Результат:

* карта старой БД;
* карта миграции;
* список UI-сценариев;
* список данных, которые реально можно перенести.

## Этап 2. Инфраструктура приложения

Статус: в работе

Задачи:

* создать Laravel 12 приложение - выполнено;
* подключить PostgreSQL - подготовлена `.env.example`, требуется реальный сервер;
* подключить Redis - подготовлен Docker service;
* подготовить Docker окружение - начато, добавлен compose для PostgreSQL+pgvector и Redis;
* подключить Vue 3, TypeScript и Vite - выполнено;
* подключить Filament - выполнено;
* добавить admin resources для модулей, переводов и legacy mapping - выполнено;
* настроить базовые тесты - выполнено;
* настроить `.env.example` - выполнено.

Результат:

* приложение запускается локально;
* есть backend, frontend и admin foundation;
* есть инструкции запуска.

## Этап 3. Базовая модель Bible

Статус: в работе

Задачи:

* создать таблицы языков - миграция и seeder созданы;
* создать таблицы модулей и переводов - миграция создана;
* создать канонические книги - миграция и seeder православного канона созданы;
* создать книги внутри модулей - миграция создана;
* создать главы - миграция создана;
* создать стихи - миграция создана;
* создать тексты стихов - миграция создана;
* заложить мультиязычность и множественные переводы - выполнено на уровне схемы.

Основные таблицы:

```text
languages
modules
translations
canonical_books
module_books
chapters
verses
verse_texts
```

Результат:

* новая структура БД может хранить несколько переводов одного канонического стиха.

## Этап 4. Импорт данных

Статус: в работе

Задачи:

* создать legacy mapping таблицы - выполнено;
* создать artisan-команды импорта - начато;
* импортировать библиотеки - выполнено для supported Bible modules через `bible:legacy:import-metadata`, commentary-модуль LOP классифицируется отдельно;
* импортировать книги - выполнено для supported Bible modules через `bible:legacy:import-metadata`;
* импортировать главы - выполнено для supported Bible modules через `bible:legacy:import-metadata`;
* импортировать стихи - начато, `bible:legacy:import-verses --library=1` переносит RST, `--all --missing-only` догружает все mapped legacy libraries;
* импортировать Strong - начато, словари импортируются через `bible:legacy:import-strong`, RST-токены через `bible:legacy:import-strong-tokens`;
* импортировать перекрёстные ссылки - выполнено для `quote.tsk` через `bible:legacy:import-cross-references`.
* импортировать календарные события - начато, `calendar:legacy:import-events` переносит `OLD/MemoryDays.xml`.

Команды:

```text
php artisan bible:legacy:seed-canonical-overrides
php artisan bible:legacy:import-metadata
php artisan bible:legacy:import-verses --library=1
php artisan bible:legacy:import-verses --all --missing-only
php artisan bible:legacy:report-skipped-verses
php artisan bible:legacy:import-strong
php artisan bible:legacy:import-strong-tokens --translation=L1_RST
php artisan bible:legacy:import-cross-references
php artisan calendar:legacy:import-events
php artisan bible:migrate-quotes
```

Результат:

* данные из старого проекта перенесены в новую структуру без переноса старой архитектуры.

## Этап 5. REST API

Статус: в работе

Задачи:

* API языков - выполнено: `GET /api/languages`;
* API книг канона - выполнено: `GET /api/canons/{canon}/books`;
* API переводов - начато: `GET /api/translations`;
* API книг перевода - начато: `GET /api/translations/{translation}/books`;
* API глав - начато: `GET /api/translations/{translation}/books/{book}/chapters/{chapter}`;
* API стихов - начато в составе chapter endpoint;
* API поиска - начато: `GET /api/search/verses` использует общий `VerseSearchService`;
* API Strong - начато: `GET /api/strong/{number}`, `GET /api/verses/{verse}/strong-tokens`;
* API cross references - начато: `GET /api/verses/{verse}/cross-references`;
* API календаря - начато: `GET /api/calendar/day`;
* API пользователя;
* API заметок и закладок;
* API групп;
* API Telegram webhook.

Результат:

* Web UI, Telegram Bot и Mini App могут работать через общий API.

## Этап 6. Bible Reader Web UI

Статус: в работе

Задачи:

* стартовый reader shell - выполнено;
* чтение глав - начато через chapter endpoint и Vue fallback;
* выбор перевода - начато в reader shell;
* выбор книги - начато в reader shell, имена берутся из `module_books` выбранного перевода;
* выбор главы - начато в reader shell;
* сохранение состояния reader - начато через localStorage, включая открытые вкладки;
* переход к стиху - начато через Search API results;
* вкладки - начато, reader хранит несколько вкладок с переводом, книгой и главой;
* режим одного перевода;
* режим нескольких переводов;
* контекстное меню стиха;
* правая панель анализа.
* вывод Strong/cross refs в правой панели - начато.

Результат:

* пользователь может читать Библию в web-интерфейсе.

## Этап 7. Поиск

Статус: начато

Задачи:

* поиск по слову - начато: `GET /api/search/verses`;
* поиск по фразе;
* поиск по ссылке - начато: `GET /api/search/verses?q=Gen.1.1`;
* поиск по номеру Стронга;
* подготовка PostgreSQL Full Text Search.

Результат:

* базовый поиск готов для сайта и Telegram Bot.

## Этап 8. Православный календарь

Статус: начато

Задачи:

* модель календарного дня - таблицы созданы;
* праздники - начат импорт из `OLD/MemoryDays.xml`;
* посты - начато через `legacy_type=10` из `OLD/MemoryDays.xml`;
* чтения дня - требуется внешний источник или отдельная ручная таблица;
* Евангелие дня - источник в legacy SQL не найден;
* Апостол дня - источник в legacy SQL не найден;
* API календаря - начато: `GET /api/calendar/day`;
* вывод в Web UI и Telegram Bot - начато для Telegram calendar commands.

Результат:

* календарь доступен через API и Telegram Bot.

## Этап 9. Telegram Bot MVP

Статус: начато

Задачи:

* `/start` - skeleton готов, отправка через Bot API подготовлена;
* `/help` - skeleton готов, отправка через Bot API подготовлена;
* `/today` - начато, показывает импортированные события текущего дня;
* `/gospel` - честный placeholder: отдельный источник чтений пока не найден;
* `/apostle` - честный placeholder: отдельный источник чтений пока не найден;
* `/random` - skeleton готов, отправка через Bot API подготовлена;
* `/search` - начато, использует общий `VerseSearchService`, поддерживает текст и ссылки вида `Gen.1.1`;
* `/calendar` - начато, показывает импортированные события текущего дня;
* `/fasting` - начато, показывает постные события текущего дня из `legacy_type=10`;
* `/settings` - skeleton готов;
* базовая локализация;
* inline-кнопки.

Результат:

* бот является самостоятельным продуктом и может использоваться без сайта и Mini App.

## Этап 10. AI Layer

Статус: не начат

Задачи:

* `AiProviderInterface`;
* `OpenAiProvider`;
* безопасная работа без `OPENAI_API_KEY`;
* анализ стиха;
* анализ слова;
* краткое и подробное толкование;
* подготовка вопросов;
* генератор Telegram-поста;
* логирование AI-запросов.

Результат:

* AI-функции работают, но приложение не падает без ключа.

## Этап 11. Заметки, закладки и теги

Статус: не начат

Задачи:

* закладки на стих, диапазон, главу и книгу;
* личные заметки;
* публичные заметки;
* групповые заметки;
* теги;
* поиск по закладкам и заметкам.

Результат:

* пользователь может сохранять и организовывать материалы.

## Этап 12. Семантический поиск

Статус: не начат

Задачи:

* подключить pgvector;
* создать embeddings для стихов;
* очередь генерации embeddings;
* semantic search API;
* similar verses API.

Результат:

* работает смысловой поиск и поиск похожих стихов.

## Этап 13. Strong Numbers

Статус: не начат

Задачи:

* импорт Strong из старого проекта;
* поиск по номеру;
* поиск по слову;
* словарная статья;
* связь слов стиха с номерами Стронга.

Результат:

* пользователь может изучать оригинальные слова через Strong.

## Этап 14. Группы

Статус: не начат

Задачи:

* группы;
* роли внутри группы;
* приглашения;
* групповые заметки;
* обсуждения;
* события;
* публикация чтения дня.

Результат:

* работает совместное изучение Библии.

## Этап 15. Telegram Mini App

Статус: не начат

Задачи:

* авторизация через Telegram initData;
* чтение Библии;
* поиск;
* закладки;
* заметки;
* AI анализ;
* календарь;
* группы.

Результат:

* Mini App покрывает сложные сценарии Telegram-пользователя.

## Этап 16. Полный UI по макетам

Статус: не начат

Задачи:

* привести Web UI к макетам из `UI/`;
* реализовать панели;
* реализовать контекстное меню;
* реализовать правую панель анализа;
* реализовать режимы чтения;
* адаптивность.

Результат:

* интерфейс соответствует визуальной концепции проекта.

---

# 9. MVP

MVP считается готовым, если работает:

* сайт;
* REST API;
* Telegram Bot;
* православный календарь;
* базовый поиск;
* AI-анализ стиха;
* AI-анализ слова;
* миграция данных;
* заметки;
* закладки.

Предлагаемая поправка: семантический поиск и полный UI по PSD лучше считать не обязательной частью MVP, а частью версии 2.0, если только они не критичны для первого релиза.

---

# 10. Версия 2.0

Версия 2.0 считается готовой, если дополнительно работают:

* семантический поиск;
* Telegram Mini App;
* группы;
* комментарии;
* события;
* Strong;
* перекрёстные ссылки;
* многоязычный интерфейс;
* многоязычный Telegram Bot;
* полный UI по PSD.

---

# 11. Предложения по корректировке технического задания

## 11.1 Разделить MVP и 2.0 жёстче

Сейчас ТЗ содержит много крупных подсистем сразу. Чтобы не расползтись, стоит определить:

* MVP: чтение, импорт, API, Telegram Bot, календарь, базовый поиск, закладки, заметки, базовый AI;
* 2.0: semantic search, Strong, группы, Mini App, полный PSD UI;
* 3.0: голос, мобильные приложения, офлайн.

## 11.2 Уточнить источник православного календаря

Нужно решить:

* календарь вводится вручную через админку;
* импортируется из внешнего источника;
* рассчитывается алгоритмически;
* комбинированный подход.

Это влияет на модель данных и сроки.

## 11.3 Уточнить права на библейские тексты

Перед импортом переводов нужно проверить лицензии:

* какие переводы можно хранить и показывать;
* какие можно использовать только локально;
* какие требуют указания источника;
* какие нельзя распространять.

## 11.4 Уточнить модель канона

Нужно заранее поддержать разные наборы книг:

* православный канон;
* протестантский канон;
* католический канон;
* апокрифы;
* модульные книги.

Иначе потом будет больно менять структуру книг и нумерацию.

## 11.5 Уточнить AI-ограничения

Нужно определить:

* лимиты запросов;
* хранение истории AI-запросов;
* стоимость;
* видимость AI-ответов;
* дисклеймеры для богословских толкований;
* режимы для разных конфессий или нейтральный режим.

## 11.6 Не начинать с полного UI

PSD важны, но для большого проекта лучше сначала сделать рабочий вертикальный срез:

```text
импорт данных -> API -> чтение главы -> поиск -> Telegram Bot
```

После этого переносить полный UI будет намного безопаснее.

## 11.7 Сначала сделать один качественный импорт

Импорт данных - фундамент проекта. Если модель Библии ошибочна, дальше сломаются поиск, Strong, ссылки, AI и UI. Поэтому анализ `OLD/` должен идти до активной разработки Reader.

---

# 12. Открытые вопросы

* Где находится старая база данных: дамп, SQLite/MySQL/PostgreSQL, конфиги Zend?
* Какие переводы Библии должны быть в первом релизе?
* Какие языки интерфейса обязательны именно для MVP?
* Нужна ли регистрация email/password в MVP или достаточно Telegram?
* Какой источник православного календаря использовать?
* Какие функции Telegram Bot критичны для первого рабочего релиза?
* Нужен ли Filament сразу или после базовой модели Bible?
* Должен ли проект стартовать как монолит Laravel + Vue или сразу разделять backend/frontend?

---

# 13. Принятые решения

## 2026-06-18

* `PROJECT_SPECIFICATION.md` создаётся как отдельная копия утверждённого ТЗ из `PROJECT_START.md`.
* Основной источник старой БД: `OLD/bible-desktop.sql`.
* Старая модель хранит тексты переводов в `verse`; в новой модели нужно разделить канонический стих и текст перевода.
* `symphony` не блокирует MVP: это старый HTML-конкорданс, новый поиск лучше строить по `verse_texts`.
* Web UI должен стартовать с reader application shell, а не с landing page.

## 2026-06-19

* Базовый канон seed строится как 77-книжный православный/RST порядок.
* `OLD/` остаётся локальным источником и не входит в Git; importer читает `OLD/bible-desktop.sql` по умолчанию.
* Первичная legacy metadata миграция ограничена поддерживаемыми языками проекта: `ru`, `uk`, `be`, `de`, `en`, и исключает legacy записи без реального Old/New Testament/Apocrypha content.
* Для глав `legacy_chapters.raw_json` можно оставлять `null`, потому что нужные поля уже нормализуются в `module_chapters` и `legacy_chapters`.
* Verse importer сначала делается на одну legacy-библиотеку; RST (`library=1`) является первым вертикальным срезом для реального чтения.
* Verse importer поддерживает all-import по всем mapped legacy libraries; на текущем dump импортировано 540527 `verse_texts`, повторный `--missing-only` не добавляет строк, 1127 стихов остаются skipped из-за отсутствующих canonical chapters.
* `bible:legacy:report-skipped-verses` показывает причины skipped legacy verses и группирует их по библиотеке/книге/главе; после исключения LOP из Bible translations текущий отчёт: 268 `missing_canonical_chapter`.
* Strong-маркеры в `verse.vers` сохраняются в `verse_texts.text_raw`, очищаются из display-поля `text` и дополнительно переносятся в `verse_strong_tokens`.
* Первый импорт Strong-токенов для `L1_RST` переносит 458984 токена из 31160 стихов с разметкой; грамматические коды старого словаря пока сохраняются как отдельные токены.
* `quote.tsk` переносится в `cross_references` через TSK parser; первый импорт сохраняет 540781 связь, а неоднозначные numeric refs остаются skipped для отдельного анализа.
* Первый endpoint чтения главы возвращает данные из `verse_texts`; frontend может мягко fallback-нуться на demo-текст, если RST ещё не импортирован.
* Reader shell теперь использует `GET /api/translations` и умеет переключать перевод, книгу и главу без перезагрузки страницы.
* Reader shell загружает книги через `GET /api/translations/{translation}/books`, поэтому показывает реальные названия из `module_books`, а не английские имена канона.
* Reader topbar search использует `GET /api/search/verses` и по клику на результат переводит reader к найденной книге, главе и стиху.
* Reader сохраняет выбранный перевод, книгу и главу в localStorage и восстанавливает их при загрузке.
* Reader tabs стали реальным состоянием: вкладка хранит перевод, книгу и главу, переключение загружает свой контекст, набор вкладок сохраняется в localStorage.
* Study API открывает Strong article, Strong tokens стиха и cross references стиха; `Gen.1.1` проверен на 8 Strong tokens и 72 cross references.
* Reader shell по клику на стих загружает Strong tokens и cross references в правую панель.
* Telegram Bot skeleton добавляет `POST /api/telegram/webhook`, проверку webhook secret и локальную генерацию `sendMessage` actions без сетевого вызова Telegram API.
* Telegram Bot может отправлять responses через Bot API при `TELEGRAM_SEND_RESPONSES=true`; webhook регистрируется командой `telegram:set-webhook`.
* Telegram Bot `/search` использует текущий DB-agnostic поиск по `verse_texts`; календарные команды читают события текущего дня из `OrthodoxCalendarService`.
* Docker infrastructure стартует с внешних сервисов: PostgreSQL 16 + pgvector и Redis; PHP пока запускается локально через Windows helper-скрипты.
* Search API добавляет базовый DB-agnostic поиск по `verse_texts.text_plain` и поиск по ссылке через canonical/module book aliases; PostgreSQL Full Text Search остаётся следующим улучшением.
* Verse search вынесен в общий `VerseSearchService`; Search API, reader UI и Telegram `/search` используют одну реализацию для текстового поиска и ссылок.
* Orthodox calendar MVP импортирует 3811 legacy events из `OLD/MemoryDays.xml`, рассчитывает православную Пасху алгоритмически и отдаёт события через `GET /api/calendar/day`.
* Постные периоды и однодневные посты в `MemoryDays.xml` идут как `legacy_type=10`; API отдаёт их отдельно в `fasting_events`, Telegram `/fasting` показывает именно их.
* В `OLD/bible-desktop.sql` нет отдельных таблиц чтений дня, Евангелия или Апостола; для этих функций нужен внешний источник или ручная админ-модель.
* Legacy `LOP` (`Толковая Библии - А.Лопухина`) классифицируется как `modules.type = commentary`, не создаёт `translations` и не попадает в Bible reader/search/Telegram как перевод.
* После исключения LOP skipped legacy verses на `database/testing.sqlite` снизились с 1127 до 268; остаток требует canonical mapping overrides для Baruch/Sirach/Joel/Psalms/Esther и chapter 0 headings.
* Добавлена таблица `legacy_canonical_chapter_overrides`; `bible:legacy:seed-canonical-overrides` заполняет 38 реальных правил, `bible:legacy:import-metadata` уже поддерживает безопасное действие `map_chapter`, а `bible:legacy:report-skipped-verses` классифицирует override actions отдельно от обычных mapping misses.
* `bible:legacy:import-verses` при mapped legacy chapter берёт target canonical book/chapter из `canonical_chapters`, поэтому `Baruch 6 -> Epistle of Jeremiah 1` импортируется как `EpJer.1.x`, а не как противоречивый `Bar.6.x`.
* После seeding overrides все 268 skipped legacy verses на `database/testing.sqlite` классифицируются: 72 `override_map_chapter`, 84 `override_requires_verse_mapping`, 20 `override_requires_book_mapping`, 65 `override_appendix`, 27 `override_heading`; обычных `missing_canonical_chapter` не осталось.
* После повторного `bible:legacy:import-metadata` mapped Baruch 6 перестаёт быть skipped, а повторный `bible:legacy:import-verses --library=10` импортирует DRB без skipped и создаёт `L10_DRB EpJer.1.1`; остаются 196 classified skipped rows для appendix/heading/requires_* cases.

## 2026-06-18

* `PROJECT_PLAN.md` создаётся как главный рабочий документ.
* Старый проект используется только как источник данных и функциональный референс.
* Новая реализация строится без переноса Zend/jQuery архитектуры.
* Мультиязычность учитывается с первого этапа проектирования.

---

# 14. Ближайшие задачи

1. Реализовать отдельную модель дополнительных материалов для appendix/heading и verse/book mapping rules для `requires_*` cases.
2. Найти или подключить внешний источник чтений дня: отдельные Евангелие и Апостол.
3. Улучшить поиск: PostgreSQL Full Text Search и подсветка совпадений.
4. Подготовить реальный PHP/app container и queue worker.
5. Продолжить reader UI: контекстное меню стиха, режим нескольких переводов и заметки.
