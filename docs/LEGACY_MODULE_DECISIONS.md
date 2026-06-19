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
```

Предварительная классификация:

* `Baruch 6` - вероятно отдельное послание Иеремии или другая схема апокрифов;
* `Sirach 52` - дополнительный/завершающий материал в legacy RST;
* `Joel 4` - разная схема деления книги Иоиля;
* `Psalms 151/152` - не входит в текущий 150-главный canonical Psalms seed;
* `Esther 11` - греческие добавления к Есфири;
* `chapter 0` в `IBSNT` - заголовки/структурные секции, не стихи.

---

# 4. Следующее решение

Нужен отдельный слой canonical mapping overrides:

* explicit map для альтернативных глав;
* возможность помечать legacy chapter как `heading`, `appendix`, `commentary` или `non_canonical`;
* отдельная модель для дополнительных глав/материалов, которые должны быть доступны, но не как обычный canonical verse.

До этого не надо расширять базовый православный канон случайными главами только ради того, чтобы убрать skipped count.
