# Migration Plan

Дата: 2026-06-18  
Статус: черновик

---

# 1. Цель

Перенести данные из старого MariaDB дампа в новую PostgreSQL-модель без переноса старой архитектуры Zend Framework.

Источник:

```text
OLD/bible-desktop.sql
```

Новая цель:

```text
PostgreSQL
Laravel migrations
Laravel console commands
legacy mapping tables
```

---

# 2. Общие правила импорта

* Импорт должен быть идемпотентным.
* Каждая команда должна писать статистику: создано, обновлено, пропущено, ошибки.
* Каждая legacy-запись должна иметь mapping в `legacy_*`.
* Исходная строка должна сохраняться в `raw_json`, если это не слишком тяжело.
* Старые `0000-00-00 00:00:00` не переносить как timestamp; заменять на `null`.
* Старый HTML очищать для `text_plain`, но сохранять исходник в `text_raw`.
* Импорт не должен зависеть от старых PHP-классов.

---

# 3. Порядок импорта

## 3.1 Languages

Источник:

```text
language
library.language
```

Цель:

```text
languages
```

Минимальные коды:

```text
ru
uk
be
de
en
```

## 3.2 Canons and Canonical Books

Источник:

```text
book.bookIndex
book.description
qualifier
```

Цель:

```text
canons
canonical_books
canonical_book_names
canonical_chapters
verses
```

Стратегия:

* использовать `bookIndex` как основной slug;
* использовать RST (`library.id = 1`) как первичный источник православной структуры, потому что там 77 книг;
* отдельно отметить неканонические/апокрифические книги;
* для переводов с 66 книг сопоставлять по `bookIndex`.

Риск:

* некоторые старые строки имеют пустой `bookIndex`;
* для них нужен ручной mapping.

## 3.3 Modules and Translations

Источник:

```text
library
```

Цель:

```text
modules
translations
legacy_libraries
```

Mapping:

```text
library.id              -> legacy_libraries.legacy_id
library.bibleName       -> translations.name
library.bibleShortName  -> translations.code
library.language        -> languages.name/code mapping
library.oldTestament    -> translations.has_old_testament
library.newTestament    -> translations.has_new_testament
library.apocrypha       -> translations.has_apocrypha
library.strongNumbers   -> translations.has_strong
library.published       -> modules.is_public
library.status          -> modules.metadata_json
library.params          -> modules.metadata_json
library.path            -> modules.metadata_json.source_path
```

## 3.4 Module Books

Источник:

```text
book
```

Цель:

```text
module_books
legacy_books
canonical_book_names
```

Mapping:

```text
book.bookID      -> legacy_books.legacy_id
book.bibleID     -> legacy_books.legacy_bible_id
book.bookIndex   -> module_books.slug + canonical_books.slug
book.fullName    -> module_books.name
book.shortName   -> module_books.aliases_json
book.pathName    -> module_books.path_name
book.chapterQty  -> module_books.chapters_count
book.versNrView  -> module_books.show_verse_numbers
```

## 3.5 Module Chapters

Источник:

```text
chapter
```

Цель:

```text
module_chapters
legacy_chapters
canonical_chapters
```

Mapping:

```text
chapter.chapterID  -> legacy_chapters.legacy_id
chapter.bookID     -> legacy_chapters.legacy_book_id
chapter.bibleID    -> legacy_chapters.legacy_bible_id
chapter.chapterNr  -> module_chapters.chapter_number
chapter.title      -> module_chapters.title
chapter.verseQty   -> module_chapters.verses_count
```

## 3.6 Verse Texts

Источник:

```text
verse
```

Цель:

```text
verses
verse_texts
legacy_verses
verse_strong_tokens
```

Mapping:

```text
verse.verseID    -> legacy_verses.legacy_id
verse.bookID     -> legacy_verses.legacy_book_id
verse.bibleID    -> legacy_verses.legacy_bible_id
verse.chapterID  -> legacy_verses.legacy_chapter_id
verse.verseNr    -> verses.verse_number
verse.vers       -> verse_texts.text_raw
```

Processing:

1. Найти `module_chapter` по legacy chapter mapping.
2. Найти или создать canonical `verse`.
3. Создать `verse_text`.
4. Очистить номер стиха в начале текста, если он дублируется.
5. Извлечь Strong-маркеры.
6. Создать `verse_strong_tokens`.

---

# 4. Парсер Strong-маркеров

Источник:

```text
verse.vers
```

Пример:

```text
1 В начале H7225 сотворил H1254 H8804 H0853 Бог H0430...
```

Правила:

* искать маркеры вида `H\d{4}` и `G\d{4}`;
* учитывать возможные грамматические коды в скобках, например `(8804)`;
* не хранить Strong-маркеры в `text_plain`;
* в `text` можно оставить display-разметку через отдельные token spans на frontend;
* исходную строку всегда сохранять в `text_raw`.

Предварительный regex:

```text
\b[HG]\d{4}\b
```

Открытый вопрос:

* как корректно привязать Strong-маркер к конкретному слову слева, если подряд идёт несколько Strong-кодов.

---

# 5. Cross References Parser

Источник:

```text
quote.tsk
```

Пример:

```text
1Ch 16:26; 1Co 8:6; 1Jo 1:1; Joh 1:1-3
```

Цель:

```text
cross_references
```

Правила:

* `quote.shortName + chapterNr + verseNr` задают исходный стих;
* `tsk` разбивается по `;`;
* каждая ссылка парсится в book alias + chapter + verse/range;
* aliases берутся из `canonical_book_names.aliases_json` и старого `book.shortName`;
* диапазон создаёт несколько `cross_references` или одну запись с metadata range - решение нужно принять после прототипа.

Предварительное решение:

* для MVP создать отдельную запись на каждый target verse;
* оригинальную строку хранить в `metadata_json.raw_ref`.

---

# 6. Strong Dictionaries

Источник:

```text
strong_lexicons
strong_numbers
```

Цель:

```text
strong_lexicons
strong_entries
```

Mapping:

```text
strong_lexicons.id        -> legacy id in metadata
strong_lexicons.name      -> strong_lexicons.name
strong_lexicons.language  -> strong_lexicons.language
strong_numbers.id_str     -> metadata_json.legacy_id
strong_numbers.strongNr   -> strong_entries.number
strong_numbers.lexiconID  -> strong_lexicons.code
strong_numbers.word       -> strong_entries.word
strong_numbers.content    -> strong_entries.raw_content + cleaned content
```

---

# 7. Что не импортировать в MVP

```text
symphony
referer
sms
session
gadgets
fonts
domain
navigation
old Zend view state
```

Причина:

* эти данные либо технические, либо UI-специфичные для старого проекта;
* они не нужны для первого вертикального среза.

---

# 8. Artisan commands

Текущая команда:

```text
php artisan bible:legacy:import-metadata
```

Она импортирует:

* `library` -> `modules`, `translations`, `legacy_libraries`;
* `book` -> `module_books`, `legacy_books`;
* `chapter` -> `module_chapters`, `legacy_chapters`.

Планируемые следующие команды:

```text
php artisan bible:legacy:import-verses
php artisan bible:legacy:import-strong
php artisan bible:legacy:import-cross-references
php artisan bible:legacy:import-all
```

Команды из исходного ТЗ:

```text
php artisan bible:migrate-library
php artisan bible:migrate-books
php artisan bible:migrate-chapters
php artisan bible:migrate-verses
php artisan bible:migrate-strong
php artisan bible:migrate-quotes
```

Решение:

* короткие имена из ТЗ можно сохранить позже как aliases;
* основные команды называются `bible:legacy:*`, чтобы было ясно, что это импорт из старой системы.

---

# 9. Проверки качества импорта

Минимальные проверки:

* количество импортированных `library` совпадает с legacy count;
* количество `verse_texts` совпадает с активными legacy verses;
* для RST Genesis 1:1 создаётся canonical verse и verse text;
* для John 3:16 можно получить несколько переводов;
* `quote` для Genesis 1:1 создаёт cross references;
* Strong `H0430` находится в словаре и связан с RST verses;
* `text_plain` не содержит Strong-маркеров.
