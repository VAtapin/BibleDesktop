# Calendar Analysis

Дата: 2026-06-18  
Источник: `OLD/MemoryDays.xml`

---

# 1. Найденный источник

```text
OLD/MemoryDays.xml
```

Характеристики:

```text
Размер: около 842 KB
Корневой элемент: MemoryDays
Количество событий: 3811
Кодировка: UTF-8
```

---

# 2. Структура события

Каждый элемент `event` содержит:

```text
s_month
s_date
f_month
f_date
name
type
```

Пример:

```xml
<event>
    <s_month>0</s_month>
    <s_date>0</s_date>
    <f_month>0</f_month>
    <f_date>0</f_date>
    <name>Светлое Христово Воскресение. Пасха</name>
    <type>0</type>
</event>
```

---

# 3. Предварительная интерпретация дат

## 3.1 Фиксированные даты

Если месяц больше 0, событие похоже на фиксированную календарную дату.

Пример:

```text
s_month=1
s_date=6
name=Святое Богоявление...
```

## 3.2 Подвижные даты

Если `s_month=0`, дата, вероятно, считается относительно Пасхи.

Примеры:

```text
s_month=0, s_date=0    Пасха
s_month=0, s_date=-7   Вход Господень в Иерусалим
s_month=0, s_date=39   Вознесение
s_month=0, s_date=49   Пятидесятница
```

## 3.3 Диапазоны

Наличие `s_*` и `f_*` указывает на start/end диапазон:

```text
s_month / s_date = начало
f_month / f_date = конец
```

Это важно для постов и многодневных периодов.

---

# 4. Типы событий

Обнаружены типы:

```text
0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10
16, 17, 18, 19, 20
100
201, 202, 203, 204, 206, 207, 208, 209, 211, 212, 213, 214, 216, 217, 219, 222, 224, 227, 232, 234, 237, 284, 287, 294, 297
301, 302, 303, 306, 308, 309
```

Частотность крупнейших типов:

```text
type 18   1249
type 7    457
type 207  352
type 204  349
type 19   317
type 17   135
type 4    96
type 6    87
type 5    71
type 202  70
```

Вывод:

* `type` нельзя использовать без расшифровки;
* для новой модели лучше завести собственную таблицу типов и сохранить старый `type` как `legacy_type`.

---

# 5. Предлагаемая новая модель календаря

## `calendar_events`

```text
id
name
event_type_id
legacy_type
date_rule_type       fixed, pascha_relative, range, computed
start_month
start_day
start_offset
end_month
end_day
end_offset
metadata_json
created_at
updated_at
```

## `calendar_event_types`

```text
id
code
name
description
sort_order
created_at
updated_at
```

## `calendar_days`

```text
id
date
year
pascha_date
tone
week
fasting_type
metadata_json
created_at
updated_at
```

## `calendar_day_events`

```text
id
calendar_day_id
calendar_event_id
sort_order
created_at
updated_at
```

---

# 6. MVP решение

Для MVP достаточно:

* импортировать события из `MemoryDays.xml`;
* считать fixed даты;
* считать события относительно Пасхи;
* сохранить `legacy_type`;
* показывать список событий дня.

Не включать в первый календарный MVP:

* тропари и кондаки, если нет источника;
* полный богослужебный устав;
* сложную типизацию всех `legacy_type`;
* ручное редактирование всех правил в UI до появления базового календаря.

---

# 7. Открытые вопросы

* По какому календарному стилю трактовать даты: старый стиль, новый стиль или оба?
* Нужен ли пользователю выбор юрисдикции/традиции календаря?
* Где источник Евангелия и Апостола дня?
* Где источник постных правил?
* Что означают все старые `type` значения?

