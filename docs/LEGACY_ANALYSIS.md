# Legacy Analysis

Дата: 2026-06-18  
Источник: `OLD/`

---

# 1. Назначение

Этот документ фиксирует результаты первичного анализа старого проекта. Старый проект используется только как источник данных и функциональный референс. Архитектура Zend Framework, jQuery UI, старые модели и контроллеры не переносятся.

---

# 2. Найденные источники данных

Основной дамп базы данных:

```text
OLD/bible-desktop.sql
```

Характеристики дампа:

```text
Размер: около 243 MB
Источник: phpMyAdmin SQL Dump
СУБД: MariaDB 10.6
Кодировка таблиц: utf8mb3
Движок таблиц: MyISAM
Дата выгрузки: 2026-06-18
```

Дополнительный календарный источник:

```text
OLD/MemoryDays.xml
```

Его нужно изучить отдельно на этапе православного календаря.

---

# 3. Структура старого приложения

Основной старый web-проект:

```text
OLD/bible.desktop/
```

Важные директории:

```text
application/controllers/
application/models/
application/views/scripts/
application/modules/mobile/
library/Zend/
public/
```

Вывод:

* это Zend Framework приложение;
* UI построен на server-rendered PHP views и jQuery;
* есть отдельный mobile module;
* новая версия не должна переносить эту структуру.

---

# 4. Таблицы старой базы

Первично обнаружены таблицы:

```text
access_level
book
bookmark
category
chapter
domain
fonts
gadgets
groups
history
language
library
members
navigation
profiles
qualifier
quote
referer
sequence
session
setting
sms
status
status_category
strong_lexicons
strong_numbers
symphony
verse
```

Примерный объём данных по ключевым таблицам:

```text
library          258
book             1 671
chapter          25 512
verse            682 984
quote            31 092
strong_lexicons  2
strong_numbers   14 696
symphony         49 158
members          13
bookmark         11
groups           5
language         5
```

---

# 5. Ключевые таблицы Bible

## 5.1 `library`

Назначение: модуль/перевод/библиотека.

Ключевые поля:

```text
id
bibleName
bibleShortName
bible
oldTestament
newTestament
apocrypha
strongNumbers
greek
language
alphabet
bookQty
path
published
access
status
description
order
```

Наблюдения:

* `library` смешивает описание перевода, тип модуля, права доступа, параметры отображения и путь импорта;
* в новой модели это надо разделить на `modules`, `translations`, `languages` и отдельные настройки доступа/публикации;
* есть флаги наличия ВЗ/НЗ/апокрифов/Strong.

## 5.2 `book`

Назначение: книги внутри конкретного `library`.

Ключевые поля:

```text
bookID
bibleID
bookIndex
pathName
fullName
shortName
chapterQty
lexiconID
description
versNrView
```

Наблюдения:

* `bookIndex` является полезным стабильным slug вроде `genesis`, `john`, `romans`;
* `fullName` и `shortName` зависят от языка/перевода;
* `description` часто хранит порядковый номер книги;
* в новой модели нужен канонический слой книги отдельно от названий в модуле.

## 5.3 `chapter`

Назначение: главы внутри конкретной книги и библиотеки.

Ключевые поля:

```text
chapterID
bookID
bibleID
chapterNr
verseQty
title
description
dataTime
admin
```

Наблюдения:

* глава привязана к конкретному переводу через `bibleID`;
* в новой модели каноническая глава должна быть отделена от главы модуля/перевода.

## 5.4 `verse`

Назначение: текст стиха конкретного перевода.

Ключевые поля:

```text
verseID
bookIndex
bookID
bibleID
chapterID
verseNr
vers
description
history
```

Наблюдения:

* `vers` хранит текст стиха;
* в RST Strong текст уже содержит inline Strong-маркеры, например `H7225`;
* `history = '0000-00-00 00:00:00'` используется как признак текущей версии;
* старый `verse` не является каноническим стихом, это именно текст перевода;
* новая модель должна разделить `verses` и `verse_texts`.

---

# 6. Cross References

## 6.1 `quote`

Назначение: перекрёстные ссылки/TSK.

Ключевые поля:

```text
id
shortName
chapterNr
verseNr
tsk
userTsk
```

Наблюдения:

* `shortName` соответствует book slug, например `genesis`;
* `tsk` хранит строку ссылок через `;`;
* данные нужно нормализовать в `cross_references`;
* `userTsk` стоит рассматривать отдельно как пользовательские связи, если там есть реальные данные.

---

# 7. Strong

## 7.1 `strong_lexicons`

Ключевые поля:

```text
id
name
copyright
comment
language
```

Содержит 2 лексикона:

```text
Greek
Hebrew
```

## 7.2 `strong_numbers`

Ключевые поля:

```text
id_str
strongNr
lexiconID
word
transliteration
pronunciation
content
```

Наблюдения:

* `strongNr` хранит значения вида `H0001`;
* `lexiconID` хранит строковый код вроде `HEB`, а не внешний ключ на `strong_lexicons.id`;
* `content` содержит HTML;
* нужна очистка/нормализация HTML, но оригинальный HTML стоит сохранить как `raw_content`.

## 7.3 `symphony`

Ключевые поля:

```text
id
word
string
language
```

Наблюдения:

* это старый словоуказатель/конкорданс;
* `string` хранит HTML/текстовые фрагменты со ссылками;
* это не нормализованная связь слова со стихом;
* для новой версии лучше строить поиск и конкорданс заново по `verse_texts`;
* импорт `symphony` не должен быть блокером MVP.

---

# 8. Пользователи и права

## 8.1 `members`

Ключевые поля:

```text
id
vorname
name
email
phone
password
forgot
status
profiles
group
lang
```

Наблюдения:

* старые пароли используют MD5;
* перенос паролей в новую систему как рабочих паролей нежелателен;
* при миграции пользователей нужен reset-password flow;
* роли надо нормализовать в Laravel permissions/roles.

## 8.2 `access_level`

Старые статусы:

```text
Программист
Администратор
Пользователь
Модератор
Волонтер
Гость
```

Новая целевая модель из ТЗ:

```text
admin
moderator
user
group_leader
```

---

# 9. Старые сценарии, которые стоит сохранить как поведение

Из `IndexController`:

* маршрутизация по ссылке на перевод/книгу/главу/стих;
* поддержка ссылок вида `libraryName/fullName/quote`;
* получение текста главы;
* диапазон стихов;
* кеширование результата;
* доступ к приватным/пользовательским библиотекам.

Из `Library` model:

* проверка доступа к библиотеке;
* учёт владельца, групп и публичности.

Эти сценарии нужно перепроектировать через Laravel services/API, а не переносить код.

---

# 10. Предварительная карта миграции

```text
language          -> languages
library           -> modules + translations + legacy_libraries
book              -> module_books + canonical_books + legacy_books
chapter           -> chapters + legacy_chapters
verse             -> verses + verse_texts + legacy_verses
quote             -> cross_references
strong_lexicons   -> strong_lexicons
strong_numbers    -> strong_entries
members           -> users, только после отдельного решения по паролям
bookmark          -> bookmarks, низкий объём, можно импортировать позже
groups            -> groups, можно импортировать позже
symphony          -> не импортировать в MVP, строить новый индекс
```

---

# 11. Риски

* Старые таблицы MyISAM не имеют строгих внешних ключей.
* Дамп использует `utf8mb3`, новая БД должна быть `utf8mb4`.
* `0000-00-00 00:00:00` невалиден для PostgreSQL date/time.
* В `verse.vers` смешаны текст стиха, номер стиха и Strong-маркеры.
* `quote.tsk` хранит ссылки строкой, нужен парсер ссылок.
* Права доступа и группы в старой БД не нормализованы.
* Пользовательские пароли MD5 нельзя переносить как современную авторизацию.

---

# 12. Ближайшие действия

1. Подготовить новую ER-модель Bible.
2. Спроектировать legacy mapping таблицы.
3. Описать парсер ссылок `quote.tsk`.
4. Описать парсер Strong-маркеров из `verse.vers`.
5. Отдельно изучить `MemoryDays.xml` для календаря.

