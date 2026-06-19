# Data Model Draft

Дата: 2026-06-18  
Статус: черновик базовой ER-модели

---

# 1. Главный принцип

Старая база хранит стих как текст конкретного перевода в таблице `verse`. Новая база должна отделять:

* каноническую структуру Библии;
* модуль/перевод;
* текст стиха в конкретном переводе;
* Strong-разметку;
* перекрёстные ссылки;
* пользовательские данные.

Это ключевое архитектурное решение. Без него параллельное чтение, разные каноны, поиск, Strong, AI и импорт новых переводов будут постоянно конфликтовать.

---

# 2. Языки

## `languages`

```text
id
code              ru, uk, be, de, en
name              Русский
native_name       Русский
is_active
sort_order
created_at
updated_at
```

Назначение:

* язык интерфейса;
* язык перевода;
* язык Telegram Bot;
* язык AI-шаблонов.

---

# 3. Канон

## `canons`

```text
id
code              orthodox, protestant, catholic, apocrypha
name
description
is_default
created_at
updated_at
```

## `canonical_books`

```text
id
canon_id
slug              genesis, exodus, matthew, john
osis_code         Gen, Exod, Matt, John
testament         old, new, apocrypha
canonical_order
default_chapters_count
is_deuterocanonical
created_at
updated_at
```

## `canonical_book_names`

```text
id
canonical_book_id
language_id
name
short_name
aliases_json
created_at
updated_at
```

Зачем отдельно:

* названия книг отличаются по языкам;
* старый `book.shortName` содержит много алиасов;
* поиск по ссылке должен понимать `Ин`, `Jn`, `John`, `Иоанна`.

## `canonical_chapters`

```text
id
canonical_book_id
number
verses_count
created_at
updated_at
```

## `verses`

```text
id
canonical_book_id
canonical_chapter_id
chapter_number
verse_number
osis_ref           John.3.16
created_at
updated_at
```

Назначение:

* один канонический стих;
* не содержит текст;
* связывает все переводы одного места.

---

# 4. Модули и переводы

## `modules`

```text
id
language_id
type              bible, apocrypha, commentary, dictionary, strong, patristics, sermons, theology, books
code              RST, KJV, UKR
name
short_name
description
version
metadata_json
is_active
is_public
sort_order
created_at
updated_at
```

## `translations`

```text
id
module_id
language_id
canon_id
code              RST
name
short_name
copyright
license
source
has_old_testament
has_new_testament
has_apocrypha
has_strong
is_default
created_at
updated_at
```

Примечание:

* `modules` оставляет архитектуру расширяемой;
* `translations` хранит Bible-специфичные поля.

## `module_books`

```text
id
module_id
translation_id
canonical_book_id
legacy_book_id
slug
name
short_name
aliases_json
path_name
book_order
chapters_count
show_verse_numbers
created_at
updated_at
```

## `module_chapters`

```text
id
module_book_id
canonical_chapter_id
legacy_chapter_id
chapter_number
title
verses_count
created_at
updated_at
```

## `verse_texts`

```text
id
verse_id
translation_id
module_book_id
module_chapter_id
legacy_verse_id
text
text_plain
text_raw
has_strong_markup
metadata_json
created_at
updated_at
```

Назначение:

* текст конкретного перевода;
* `text_raw` хранит исходный старый текст;
* `text_plain` нужен для поиска;
* `text` может хранить очищенный display-текст.

---

## `legacy_canonical_chapter_overrides`

```text
id
legacy_bible_id          nullable, null means global override
legacy_book_slug
legacy_chapter_number
action                   map_chapter, heading, appendix, non_canonical, requires_verse_mapping, requires_book_mapping
target_book_slug
target_chapter_number
reason
note
metadata_json
created_at
updated_at
```

Назначение:

* хранить ручные правила для legacy chapters, которые не совпадают с базовым canonical seed;
* не расширять канон случайными дополнительными главами;
* дать importer/report явный способ отличать реальные ошибки mapping от заголовков, приложений и альтернативной нумерации.

---

## `legacy_supplemental_texts`

```text
id
module_id
translation_id
module_book_id
module_chapter_id
legacy_verse_id
legacy_bible_id
legacy_book_id
legacy_chapter_id
legacy_book_slug
legacy_chapter_number
legacy_verse_number
type                  heading, appendix, non_canonical
title
text
text_plain
text_raw
metadata_json
created_at
updated_at
```

Назначение:

* хранить legacy heading/appendix/non-canonical материалы отдельно от `verse_texts`;
* не засорять canonical `verses` дополнительными главами;
* оставить эти материалы доступными для будущего API, reader sidebar или admin review.

---

# 5. Strong

## `strong_lexicons`

```text
id
code              HEB, GRK
name
language
copyright
comment
created_at
updated_at
```

## `strong_entries`

```text
id
lexicon_id
number            H0001, G0026
word
transliteration
pronunciation
content
raw_content
created_at
updated_at
```

## `verse_strong_tokens`

```text
id
verse_text_id
verse_id
strong_entry_id
strong_number
token_order
surface_text
grammar_code
created_at
updated_at
```

Назначение:

* связь слова/позиции в тексте с номером Стронга;
* создаётся парсером из `verse.vers`;
* не зависит от старого `symphony`.

---

# 6. Cross References

## `cross_references`

```text
id
source_verse_id
target_verse_id
type              tsk, parallel, quotation, thematic, user
source            legacy_quote, manual, ai, group
metadata_json
created_at
updated_at
```

## `reference_groups`

```text
id
name
description
type
created_at
updated_at
```

## `reference_group_items`

```text
id
reference_group_id
cross_reference_id
sort_order
created_at
updated_at
```

---

# 7. Search

## `search_indexes`

```text
id
verse_text_id
translation_id
language_id
text
search_vector
created_at
updated_at
```

PostgreSQL implementation:

* generated `tsvector` or explicit `tsvector` column;
* language-specific configuration where possible;
* fallback simple config for mixed Slavic text.

## `verse_embeddings`

```text
id
verse_text_id
translation_id
language_id
model
embedding
content_hash
created_at
updated_at
```

PostgreSQL implementation:

* `pgvector`;
* created after MVP unless semantic search moves into MVP.

---

# 8. Users, Notes, Bookmarks

## `users`

```text
id
name
email
password
telegram_id
telegram_username
language_id
avatar_url
settings_json
created_at
updated_at
```

Важно:

* старые MD5-пароли не переносить как рабочие;
* мигрированным пользователям нужен сброс пароля.

## `bookmarks`

```text
id
user_id
verse_id
start_verse_id
end_verse_id
module_book_id
module_chapter_id
title
description
metadata_json
created_at
updated_at
```

## `notes`

```text
id
user_id
group_id
verse_id
start_verse_id
end_verse_id
visibility          private, public, group
body
metadata_json
created_at
updated_at
```

## `tags`

```text
id
user_id
name
slug
created_at
updated_at
```

## `taggables`

```text
tag_id
taggable_type
taggable_id
```

---

# 9. Legacy Mapping

## `legacy_libraries`

```text
id
legacy_id
module_id
translation_id
raw_json
created_at
updated_at
```

## `legacy_books`

```text
id
legacy_id
legacy_bible_id
module_book_id
canonical_book_id
raw_json
created_at
updated_at
```

## `legacy_chapters`

```text
id
legacy_id
legacy_book_id
legacy_bible_id
module_chapter_id
canonical_chapter_id
raw_json
created_at
updated_at
```

## `legacy_verses`

```text
id
legacy_id
legacy_book_id
legacy_chapter_id
legacy_bible_id
verse_id
verse_text_id
raw_json
created_at
updated_at
```

---

# 10. Индексы и ограничения

Минимальные уникальные ограничения:

```text
languages.code
canons.code
canonical_books(canon_id, slug)
canonical_chapters(canonical_book_id, number)
verses(canonical_book_id, chapter_number, verse_number)
modules.code
translations.code
module_books(translation_id, canonical_book_id)
module_chapters(module_book_id, chapter_number)
verse_texts(translation_id, verse_id)
strong_entries(number)
cross_references(source_verse_id, target_verse_id, type)
```

Критичные индексы:

```text
verse_texts(translation_id, module_chapter_id)
verse_texts(verse_id)
module_books(translation_id, book_order)
canonical_book_names(language_id)
verse_strong_tokens(strong_entry_id)
cross_references(source_verse_id)
cross_references(target_verse_id)
```

---

# 11. Решения для MVP

В MVP включить:

* `languages`;
* `canons`;
* `canonical_books`;
* `canonical_book_names`;
* `canonical_chapters`;
* `verses`;
* `modules`;
* `translations`;
* `module_books`;
* `module_chapters`;
* `verse_texts`;
* `legacy_*`;
* базовый `cross_references`;
* базовый `strong_entries`, если импорт Strong не тормозит вертикальный срез.

Отложить до 2.0:

* `verse_embeddings`;
* полноценный semantic search;
* group study model;
* расширенные события;
* импорт старого `symphony`.
