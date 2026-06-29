#!/usr/bin/env python3
"""
Извлечение церковнославянских слов под титлами и проверка доменов .ru.

Источник по умолчанию:
https://azbyka.ru/otechnik/Spravochniki/slovar-tserkovnoslavjanskih-slov-pod-titlami/

Примеры:
    python tools/titlo_domain_checker.py
    python tools/titlo_domain_checker.py --no-check
    python tools/titlo_domain_checker.py --limit 20 --delay 1.5
    python tools/titlo_domain_checker.py --only-free

Скрипт использует только стандартную библиотеку Python. API-ключи и
платные сервисы не нужны.
"""

from __future__ import annotations

import argparse
import csv
import json
import re
import socket
import ssl
import sys
import time
import unicodedata
import urllib.error
import urllib.request
from dataclasses import asdict, dataclass
from datetime import datetime, timedelta, timezone
from html.parser import HTMLParser
from pathlib import Path


DEFAULT_URL = (
    "https://azbyka.ru/otechnik/Spravochniki/"
    "slovar-tserkovnoslavjanskih-slov-pod-titlami/"
)
USER_AGENT = "TitloDomainChecker/1.0 (+local naming research)"
WHOIS_SERVER = "whois.tcinet.ru"
AVAILABLE_MARKERS = (
    "no entries found",
    "no entries found for the selected source",
)

# Обычные церковнославянские буквы.
TRANSLITERATION = {
    "а": "a",
    "б": "b",
    "в": "v",
    "г": "g",
    "д": "d",
    "е": "e",
    "ё": "yo",
    "ж": "zh",
    "з": "z",
    "и": "i",
    "й": "y",
    "к": "k",
    "л": "l",
    "м": "m",
    "н": "n",
    "о": "o",
    "п": "p",
    "р": "r",
    "с": "s",
    "т": "t",
    "у": "u",
    "ф": "f",
    "х": "kh",
    "ц": "ts",
    "ч": "ch",
    "ш": "sh",
    "щ": "shch",
    "ъ": "",
    "ы": "y",
    "ь": "",
    "э": "e",
    "ю": "yu",
    "я": "ya",
    "є": "e",
    "і": "i",
    "ї": "i",
    "ѕ": "dz",
    "ѡ": "o",
    "ѿ": "ot",
    "ѻ": "o",
    "ѽ": "o",
    "ѣ": "e",
    "ѧ": "ya",
    "ѩ": "ya",
    "ѫ": "u",
    "ѭ": "yu",
    "ѯ": "ks",
    "ѱ": "ps",
    "ѳ": "f",
    "ѵ": "i",
    "ꙋ": "u",
    "ꙗ": "ya",
    "ꙙ": "ya",
    "ѥ": "ye",
}

# Надстрочные церковнославянские буквы Unicode U+2DE0..U+2DFF.
# В отличие от ударений и знака титла, это настоящая часть сокращения:
# например блгⷣть -> blgdt.
COMBINING_LETTERS = {
    "\u2de0": "b",
    "\u2de1": "v",
    "\u2de2": "g",
    "\u2de3": "d",
    "\u2de4": "zh",
    "\u2de5": "z",
    "\u2de6": "k",
    "\u2de7": "l",
    "\u2de8": "m",
    "\u2de9": "n",
    "\u2dea": "o",
    "\u2deb": "p",
    "\u2dec": "r",
    "\u2ded": "s",
    "\u2dee": "t",
    "\u2def": "kh",
    "\u2df0": "ts",
    "\u2df1": "ch",
    "\u2df2": "sh",
    "\u2df3": "shch",
    "\u2df4": "f",
    "\u2df5": "s",
    "\u2df6": "e",
    "\u2df7": "u",
    "\u2df8": "ya",
    "\u2df9": "u",
    "\u2dfa": "ya",
    "\u2dfb": "o",
    "\u2dfc": "ksi",
    "\u2dfd": "psi",
    "\u2dfe": "f",
    "\u2dff": "i",
}

TITLO_MARKS = {
    "\u0483",  # COMBINING CYRILLIC TITLO
    "\u0487",  # COMBINING CYRILLIC POKRYTIE
}

DASH_RE = re.compile(r"\s+[–—−]\s+")
SPLIT_VARIANTS_RE = re.compile(r"\s*(?:,|;|\s+или\s+)\s*", re.IGNORECASE)
DOMAIN_RE = re.compile(r"^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$")


@dataclass(frozen=True)
class TitloWord:
    source: str
    transcription: str
    expanded: str
    domain: str


class PonomarSpanParser(HTMLParser):
    """Собирает текст из span.ponomar, где расположен словарь."""

    def __init__(self) -> None:
        super().__init__(convert_charrefs=True)
        self.depth = 0
        self.buffer: list[str] = []
        self.entries: list[str] = []

    def handle_starttag(
        self,
        tag: str,
        attrs: list[tuple[str, str | None]],
    ) -> None:
        attributes = dict(attrs)
        classes = set((attributes.get("class") or "").split())
        if tag == "span" and "ponomar" in classes:
            if self.depth == 0:
                self.buffer = []
            self.depth += 1
        elif self.depth:
            self.depth += 1

    def handle_endtag(self, tag: str) -> None:
        if not self.depth:
            return
        self.depth -= 1
        if self.depth == 0:
            text = " ".join("".join(self.buffer).split())
            if text:
                self.entries.append(text)
            self.buffer = []

    def handle_data(self, data: str) -> None:
        if self.depth:
            self.buffer.append(data)


def download_page(url: str, timeout: float, insecure: bool = False) -> str:
    request = urllib.request.Request(
        url,
        headers={
            "User-Agent": USER_AGENT,
            "Accept": "text/html,application/xhtml+xml",
            "Accept-Language": "ru,en;q=0.7",
        },
    )
    context = ssl._create_unverified_context() if insecure else None
    with urllib.request.urlopen(request, timeout=timeout, context=context) as response:
        encoding = response.headers.get_content_charset() or "utf-8"
        return response.read().decode(encoding, errors="replace")


def has_titlo(value: str) -> bool:
    return any(
        character in TITLO_MARKS or 0x2DE0 <= ord(character) <= 0x2DFF
        for character in value
    )


def clean_display_word(value: str) -> str:
    value = value.strip(" \t\r\n.():[]«»\"'")
    # В словаре встречаются пояснения перед словом; они находятся вне span,
    # но эта очистка защищает парсер от будущих изменений разметки.
    return " ".join(value.split())


def normalize_expanded(value: str) -> str:
    """Убирает ударения, сохраняя церковнославянское написание."""
    result: list[str] = []
    # NFC сохраняет самостоятельные буквы й/ї, но оставляет ударения
    # отдельными комбинируемыми знаками.
    for character in unicodedata.normalize("NFC", value):
        if character in COMBINING_LETTERS:
            result.append(character)
            continue
        if unicodedata.combining(character):
            continue
        result.append(character)
    return unicodedata.normalize("NFC", "".join(result)).strip()


def titlo_to_latin(value: str) -> str:
    """
    Транслитерирует именно сокращённую форму под титлом.

    бл҃года́ть -> blgodat
    гл҃ати     -> glati
    """
    output: list[str] = []
    for character in unicodedata.normalize("NFC", value.lower()):
        if character in COMBINING_LETTERS:
            output.append(COMBINING_LETTERS[character])
            continue
        if character in TITLO_MARKS or unicodedata.combining(character):
            continue
        output.append(TRANSLITERATION.get(character, character))
    return re.sub(r"[^a-z0-9-]", "", "".join(output))


def parse_dictionary(html: str, min_length: int) -> list[TitloWord]:
    parser = PonomarSpanParser()
    parser.feed(html)

    words: list[TitloWord] = []
    seen: set[tuple[str, str]] = set()
    for entry in parser.entries:
        parts = DASH_RE.split(entry, maxsplit=1)
        if len(parts) != 2:
            continue
        abbreviated_side, expanded_side = parts
        expanded = normalize_expanded(expanded_side)

        for variant in SPLIT_VARIANTS_RE.split(abbreviated_side):
            source = clean_display_word(variant)
            if not source or source.startswith("-") or not has_titlo(source):
                continue
            transcription = titlo_to_latin(source)
            if len(transcription) < min_length or not DOMAIN_RE.fullmatch(transcription):
                continue
            key = (source, transcription)
            if key in seen:
                continue
            seen.add(key)
            words.append(
                TitloWord(
                    source=source,
                    transcription=transcription,
                    expanded=expanded,
                    domain=f"{transcription}.ru",
                )
            )

    words.sort(key=lambda word: (word.transcription, word.source))
    return words


def query_ru_whois(domain: str, timeout: float) -> str:
    """Возвращает available, registered, rate_limited или unknown."""
    try:
        with socket.create_connection((WHOIS_SERVER, 43), timeout=timeout) as connection:
            connection.settimeout(timeout)
            connection.sendall((domain + "\r\n").encode("ascii"))
            chunks: list[bytes] = []
            size = 0
            while size < 256_000:
                chunk = connection.recv(4096)
                if not chunk:
                    break
                chunks.append(chunk)
                size += len(chunk)
    except (OSError, TimeoutError, socket.timeout):
        return "unknown"

    answer = b"".join(chunks).decode("utf-8", errors="ignore").lower()
    if any(marker in answer for marker in AVAILABLE_MARKERS):
        return "available"
    if "limit exceeded" in answer or "try again later" in answer:
        return "rate_limited"
    if "domain:" in answer or "registrar:" in answer:
        return "registered"
    return "unknown"


def load_cache(path: Path, max_age_days: int) -> dict[str, dict[str, str]]:
    if not path.exists():
        return {}
    try:
        payload = json.loads(path.read_text(encoding="utf-8"))
    except (OSError, ValueError):
        return {}
    if not isinstance(payload, dict):
        return {}

    minimum_date = datetime.now(timezone.utc) - timedelta(days=max_age_days)
    result: dict[str, dict[str, str]] = {}
    for domain, record in payload.items():
        if not isinstance(record, dict):
            continue
        try:
            checked_at = datetime.fromisoformat(record["checked_at"])
        except (KeyError, TypeError, ValueError):
            continue
        if checked_at.tzinfo is None:
            checked_at = checked_at.replace(tzinfo=timezone.utc)
        if checked_at >= minimum_date and record.get("status"):
            result[domain] = record
    return result


def save_cache(path: Path, cache: dict[str, dict[str, str]]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    temporary = path.with_suffix(path.suffix + ".tmp")
    temporary.write_text(
        json.dumps(cache, ensure_ascii=False, indent=2, sort_keys=True),
        encoding="utf-8",
    )
    temporary.replace(path)


def check_domains(
    words: list[TitloWord],
    *,
    limit: int | None,
    delay: float,
    timeout: float,
    cache_path: Path,
    cache_days: int,
) -> dict[str, str]:
    cache = load_cache(cache_path, cache_days)
    statuses: dict[str, str] = {}
    domains = list(dict.fromkeys(word.domain for word in words))
    if limit is not None:
        domains = domains[:limit]

    uncached_position = 0
    uncached_total = sum(domain not in cache for domain in domains)
    for domain in domains:
        if domain in cache:
            statuses[domain] = cache[domain]["status"]
            continue

        uncached_position += 1
        print(
            f"[{uncached_position}/{uncached_total}] {domain} … ",
            end="",
            flush=True,
        )
        status = query_ru_whois(domain, timeout)
        print(status)
        statuses[domain] = status
        cache[domain] = {
            "status": status,
            "checked_at": datetime.now(timezone.utc).isoformat(),
        }
        save_cache(cache_path, cache)
        if delay > 0 and uncached_position < uncached_total:
            time.sleep(delay)
    return statuses


def write_csv(
    path: Path,
    words: list[TitloWord],
    statuses: dict[str, str],
    *,
    only_free: bool,
) -> int:
    path.parent.mkdir(parents=True, exist_ok=True)
    written = 0
    with path.open("w", encoding="utf-8-sig", newline="") as output:
        writer = csv.DictWriter(
            output,
            fieldnames=(
                "titlo_word",
                "transcription",
                "expanded",
                "domain",
                "status",
            ),
        )
        writer.writeheader()
        for word in words:
            status = statuses.get(word.domain, "unchecked")
            if only_free and status != "available":
                continue
            writer.writerow(
                {
                    "titlo_word": word.source,
                    "transcription": word.transcription,
                    "expanded": word.expanded,
                    "domain": word.domain,
                    "status": status,
                }
            )
            written += 1
    return written


def write_json(
    path: Path,
    words: list[TitloWord],
    statuses: dict[str, str],
    *,
    only_free: bool,
) -> int:
    records = []
    for word in words:
        status = statuses.get(word.domain, "unchecked")
        if only_free and status != "available":
            continue
        record = asdict(word)
        record["status"] = status
        records.append(record)
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(
        json.dumps(records, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )
    return len(records)


def parse_positive_int(value: str) -> int:
    number = int(value)
    if number <= 0:
        raise argparse.ArgumentTypeError("значение должно быть больше нуля")
    return number


def parse_arguments(argv: list[str] | None = None) -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description=(
            "Читает словарь слов под титлами, строит краткую латинскую "
            "транскрипцию и проверяет домены .ru."
        ),
        formatter_class=argparse.ArgumentDefaultsHelpFormatter,
    )
    parser.add_argument("--url", default=DEFAULT_URL, help="адрес словаря")
    parser.add_argument(
        "--min-length",
        type=parse_positive_int,
        default=5,
        help="минимальная длина латинской транскрипции",
    )
    parser.add_argument(
        "--no-check",
        action="store_true",
        help="только извлечь слова, не обращаться к WHOIS",
    )
    parser.add_argument(
        "--insecure",
        action="store_true",
        help=(
            "разрешить загрузку при недействительном TLS-сертификате источника; "
            "использовать только для доверенного URL"
        ),
    )
    parser.add_argument(
        "--limit",
        type=parse_positive_int,
        help="проверить не больше указанного числа уникальных доменов",
    )
    parser.add_argument(
        "--delay",
        type=float,
        default=1.0,
        help="пауза между WHOIS-запросами, секунд",
    )
    parser.add_argument(
        "--timeout",
        type=float,
        default=10.0,
        help="тайм-аут загрузки страницы и WHOIS-запроса",
    )
    parser.add_argument(
        "--cache",
        type=Path,
        default=Path("titlo_domain_cache.json"),
        help="файл кеша результатов WHOIS",
    )
    parser.add_argument(
        "--cache-days",
        type=parse_positive_int,
        default=7,
        help="срок действия записи кеша",
    )
    parser.add_argument(
        "--only-free",
        action="store_true",
        help="сохранить только свободные домены",
    )
    parser.add_argument(
        "--output",
        type=Path,
        default=Path("titlo_domains.csv"),
        help="выходной CSV или JSON",
    )
    args = parser.parse_args(argv)
    if args.delay < 0:
        parser.error("--delay не может быть отрицательным")
    if args.timeout <= 0:
        parser.error("--timeout должен быть больше нуля")
    return args


def main(argv: list[str] | None = None) -> int:
    args = parse_arguments(argv)
    try:
        print(f"Загрузка словаря: {args.url}")
        html = download_page(args.url, args.timeout, insecure=args.insecure)
    except (OSError, TimeoutError, urllib.error.URLError) as error:
        print(f"Не удалось загрузить словарь: {error}", file=sys.stderr)
        return 2

    words = parse_dictionary(html, args.min_length)
    if not words:
        print(
            "Слова под титлами не найдены: возможно, изменилась разметка страницы.",
            file=sys.stderr,
        )
        return 3

    print(
        f"Найдено форм длиной от {args.min_length} знаков: {len(words)}; "
        f"уникальных доменов: {len({word.domain for word in words})}."
    )
    statuses: dict[str, str] = {}
    if not args.no_check:
        statuses = check_domains(
            words,
            limit=args.limit,
            delay=args.delay,
            timeout=args.timeout,
            cache_path=args.cache,
            cache_days=args.cache_days,
        )

    if args.output.suffix.lower() == ".json":
        written = write_json(
            args.output,
            words,
            statuses,
            only_free=args.only_free,
        )
    else:
        written = write_csv(
            args.output,
            words,
            statuses,
            only_free=args.only_free,
        )

    available = sum(status == "available" for status in statuses.values())
    registered = sum(status == "registered" for status in statuses.values())
    unknown = sum(
        status not in {"available", "registered"} for status in statuses.values()
    )
    print(
        f"Свободно: {available}; занято: {registered}; "
        f"не удалось определить: {unknown}."
    )
    print(f"Записано строк: {written}. Файл: {args.output.resolve()}")

    # Показываем обязательные контрольные примеры, если они есть на странице.
    examples = {"blgodat", "glati"}
    for word in words:
        if word.transcription in examples:
            print(
                f"  {word.source} -> {word.transcription} -> "
                f"{word.expanded} -> {word.domain}"
            )
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except KeyboardInterrupt:
        print("\nОстановлено пользователем.", file=sys.stderr)
        raise SystemExit(130)
