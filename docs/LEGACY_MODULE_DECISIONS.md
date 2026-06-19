# Legacy Module Decisions

Дата: 2026-06-19

---

# 1. Зачем нужен документ

Legacy `library` смешивает переводы Библии, толкования и технические/производные материалы. Для новой модели нельзя автоматически считать каждую строку с `library.bible = Y` полноценным переводом, иначе commentary-контент попадает в reader, поиск и Telegram как будто это библейский текст.

---

# 2. Принятое решение по LOP

Legacy library:

```text
id: 376
short: LOP
name: Толковая Библии - А.Лопухина
```

Решение:

* импортировать как `modules.type = commentary`;
* не создавать запись в `translations`;
* не импортировать его содержимое в `verse_texts`;
* оставить `legacy_libraries.module_id` и `raw_json`, чтобы позже сделать отдельный importer комментариев.

Причина:

* содержимое содержит толкования и большие HTML-фрагменты;
* структура глав не совпадает с каноном и даёт искусственные `missing_canonical_chapter`;
* reader, Search API и Telegram Bot должны работать только с Bible translations.

Практический эффект на текущем `database/testing.sqlite` после повторного metadata import:

```text
До решения: 1127 skipped legacy verses
После решения: 268 skipped legacy verses
Убрано из Bible-import проблем: 859 L376_LOP строк
```

---

# 3. Оставшиеся canonical mapping расхождения

После исключения LOP остаются реальные случаи, которые нельзя решать blanket-исключением:

```text
L10_DRB         Baruch 6       72
L1_RST          Sirach 52      38
L3_UKR          Joel 4         21
L5_LB           Joel 4         21
L325_UKH        Joel 4         21
L359_SCH2000NEU Joel 4         21
L336_SLR        Psalms 151      8
L4_BBS          Psalms 151/152 13
L325_UKH        Esther 11       6
L492_IBSNT      chapter 0 headings, 27 total
L3_UKR          2 Thessalonians 4, 20 total
```

Предварительная классификация:

* `Baruch 6` - вероятно отдельное послание Иеремии или другая схема апокрифов;
* `Sirach 52` - дополнительный/завершающий материал в legacy RST;
* `Joel 4` - разная схема деления книги Иоиля;
* `Psalms 151/152` - не входит в текущий 150-главный canonical Psalms seed;
* `Esther 11` - греческие добавления к Есфири;
* `chapter 0` в `IBSNT` - заголовки/структурные секции, не стихи.
* `L3_UKR 2thessalonians 4` - legacy anomaly: строка лежит под 2 Thessalonians, но текст и title относятся к 1 Timothy 1.

---

# 4. Canonical mapping overrides

Добавлен первый слой canonical mapping overrides:

```text
legacy_canonical_chapter_overrides
```

Таблица поддерживает:

* `legacy_bible_id` - конкретная legacy library или `null` для общего правила;
* `legacy_book_slug`;
* `legacy_chapter_number`;
* `action`;
* `target_book_slug`;
* `target_chapter_number`;
* `reason`, `note`, `metadata_json`.

Первое поддержанное действие:

```text
map_chapter
```

`bible:legacy:import-metadata` применяет `map_chapter` только если обычный canonical lookup не нашёл главу. Это защищает нормальные главы от случайного override.

`bible:legacy:report-skipped-verses` читает overrides и показывает такие строки как `override_{action}` вместо общего `missing_canonical_chapter`.

Для альтернативной нумерации стихов добавлен второй слой:

```text
legacy_canonical_verse_overrides
```

Первый поддержанный кейс:

```text
L3_UKR, L5_LB, L325_UKH, L359_SCH2000NEU
legacy Joel 3:1-5  -> canonical Joel 2:28-32
legacy Joel 4:1-21 -> canonical Joel 3:1-21
```

`bible:legacy:import-verses` применяет `map_verse` до проверки chapter mapping, поэтому может импортировать стихи из legacy-главы без `canonical_chapter_id`.

Следующие действия ещё нужно обработать там, где это нужно:

```text
heading
appendix
non_canonical
requires_book_mapping
```

До этого не надо расширять базовый православный канон случайными главами только ради того, чтобы убрать skipped count.

Реальные правила заполняются командой:

```text
php artisan bible:legacy:seed-canonical-overrides
```

Текущее состояние после seeding на `database/testing.sqlite`:

```text
override_map_chapter: 72
override_requires_verse_mapping: 84
override_requires_book_mapping: 20
override_appendix: 65
override_heading: 27
missing_canonical_chapter: 0
```

После повторного `bible:legacy:import-metadata` mapped `Baruch 6 -> Epistle of Jeremiah 1` перестаёт быть skipped. Повторный `bible:legacy:import-verses --library=10` импортирует DRB без skipped и создаёт:

```text
L10_DRB EpJer.1.1
```

После повторного импорта affected Joel-библиотек без `--missing-only` `requires_verse_mapping` закрывается для Joel. Важно: если эти библиотеки уже импортировались до появления verse-level rules, повторный `--missing-only` не исправит старые `Joel 3` mappings.

Фактический report на `database/testing.sqlite` после seeding verse overrides:

```text
Skipped legacy verses: 112
override_appendix: 65
override_requires_book_mapping: 20
override_heading: 27
```

Проверенные импортированные примеры:

```text
L3_UKR  legacy 91146 -> Joel.2.28
L3_UKR  legacy 91151 -> Joel.3.1
L5_LB   legacy 153420 -> Joel.2.28
L5_LB   legacy 153425 -> Joel.3.1
```

Осторожность по `L3_UKR 2thessalonians 4`: это текст `1 Timothy 1`, но в той же библиотеке есть нормальная `1timothy 1`. Автоматический `map_verse` перезапишет реальную `1Tim 1`, поэтому этот кейс сохраняется как supplemental text с `type=requires_book_mapping`.

`bible:legacy:import-supplemental-texts` переносит appendix/heading/non-canonical/requires_book_mapping rows в отдельную таблицу `legacy_supplemental_texts`. На `database/testing.sqlite` импортировано:

```text
appendix: 65
heading: 27
requires_book_mapping: 20
```
