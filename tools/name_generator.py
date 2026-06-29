#!/usr/bin/env python3
"""
Автономный генератор названий для православного интернет-проекта.

Генерация, фильтрация и оценка выполняются локально, без нейросетей и
платных API. Проверка доменов использует публичные RDAP/WHOIS-сервисы и
запускается только для небольшого числа финалистов.

Примеры:
    python tools/name_generator.py --count 500000 --top 200
    python tools/name_generator.py --count 1000000 --top 100 \
        --check-domains --domain-limit 40 --min-free-domains 1
    python tools/name_generator.py --dictionary russian_words.txt \
        --blocked my_brands.txt
"""

from __future__ import annotations

import argparse
import csv
import difflib
import heapq
import json
import random
import re
import socket
import sys
import time
import unicodedata
import urllib.error
import urllib.request
from dataclasses import dataclass, field
from pathlib import Path
from typing import Iterable, Iterator


CYRILLIC_VOWELS = frozenset("аеёиоуыэюя")
LATIN_VOWELS = frozenset("aeiouy")

TRANSLIT_TABLE = str.maketrans(
    {
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
    }
)

# Корни дают смысловую связь, но не превращают выдачу в набор обычных слов.
SEMANTIC_ROOTS = (
    "благ",
    "вер",
    "свет",
    "собор",
    "мир",
    "слов",
    "вест",
    "дух",
    "лик",
    "глас",
    "лог",
    "тео",
    "агап",
    "кири",
    "соф",
    "акси",
)

SEMANTIC_STEMS = (
    "благо",
    "веро",
    "свето",
    "соборо",
    "миро",
    "слово",
    "весто",
    "духо",
    "лико",
    "гласо",
    "лого",
    "тео",
    "агапо",
    "кири",
    "софи",
    "акси",
)

SEMANTIC_STEM_WEIGHTS = (10, 12, 12, 8, 10, 8, 6, 6, 5, 6, 3, 5, 3, 2, 3, 1)

THEMATIC_RELEVANCE = {
    "благ": 9,
    "вер": 10,
    "свет": 10,
    "собор": 9,
    "мир": 8,
    "слов": 7,
    "вест": 7,
    "дух": 7,
    "глас": 7,
    "лик": 5,
    "тео": 5,
    "соф": 4,
    "лог": 3,
    "агап": 3,
    "кири": 2,
    "акси": -4,
}

SEMANTIC_ENDINGS = (
    "вия",
    "дара",
    "дея",
    "елия",
    "ения",
    "иана",
    "ика",
    "илия",
    "иона",
    "ира",
    "лия",
    "мира",
    "ника",
    "ония",
    "ора",
    "ория",
    "рия",
    "сана",
    "сфера",
    "тея",
    "фора",
)

STYLE_PARTS = {
    "slavic": {
        "onsets": (
            "б",
            "в",
            "г",
            "д",
            "ж",
            "з",
            "к",
            "л",
            "м",
            "н",
            "п",
            "р",
            "с",
            "т",
            "х",
            "ч",
            "бл",
            "бр",
            "вл",
            "вр",
            "гл",
            "гр",
            "др",
            "зл",
            "кр",
            "мл",
            "пр",
            "сл",
            "св",
            "тр",
            "хр",
        ),
        "vowels": ("а", "е", "и", "о", "у", "я"),
        "codas": ("", "", "", "в", "й", "л", "м", "н", "р", "с", "т"),
        "endings": (
            "а",
            "ия",
            "ея",
            "ика",
            "ина",
            "ора",
            "ово",
            "арь",
            "ель",
            "ение",
            "ство",
            "иян",
        ),
    },
    "church": {
        "onsets": (
            "б",
            "в",
            "г",
            "д",
            "з",
            "к",
            "л",
            "м",
            "н",
            "п",
            "р",
            "с",
            "т",
            "х",
            "ч",
            "бл",
            "вс",
            "гл",
            "гр",
            "кр",
            "пр",
            "сл",
            "св",
            "ст",
            "тр",
            "хр",
        ),
        "vowels": ("а", "е", "и", "о", "у"),
        "codas": ("", "", "", "в", "л", "м", "н", "р", "с", "т"),
        "endings": (
            "ия",
            "ие",
            "ика",
            "ион",
            "ора",
            "ель",
            "арий",
            "ония",
            "ея",
            "ум",
            "ос",
        ),
    },
    "greek": {
        "onsets": (
            "г",
            "д",
            "з",
            "к",
            "л",
            "м",
            "н",
            "п",
            "р",
            "с",
            "т",
            "ф",
            "х",
            "кл",
            "кр",
            "кс",
            "пр",
            "ск",
            "ст",
            "тр",
            "фр",
            "хр",
        ),
        "vowels": ("а", "е", "и", "о", "у"),
        "codas": ("", "", "", "", "к", "л", "н", "р", "с", "т"),
        "endings": (
            "а",
            "ея",
            "ия",
            "ика",
            "ион",
            "ида",
            "ора",
            "ос",
            "ис",
            "он",
            "ея",
            "иум",
        ),
    },
}

BUILTIN_WORDS = frozenset(
    {
        "библия",
        "церковь",
        "православие",
        "молитва",
        "календарь",
        "святыня",
        "апостол",
        "евангелие",
        "христианство",
        "сообщество",
        "общение",
        "знакомства",
        "дружба",
        "новости",
        "помощь",
        "надежда",
        "любовь",
        "вера",
        "истина",
        "жизнь",
        "свет",
        "слово",
        "словения",
        "собор",
        "предание",
        "азбука",
        "логос",
    }
)

BUILTIN_BRANDS = (
    "google",
    "telegram",
    "avito",
    "vk",
    "vkontakte",
    "facebook",
    "instagram",
    "youtube",
    "tiktok",
    "whatsapp",
    "yandex",
    "ozon",
    "logos",
    "azbyka",
    "predanie",
    "pravmir",
    "pravoslavie",
)

# Проверяются и кириллица, и латиница. Список намеренно редактируемый и
# консервативный: лучше вручную расширить его под страны запуска проекта.
NEGATIVE_CYRILLIC = (
    "адск",
    "бес",
    "бля",
    "гад",
    "говн",
    "грех",
    "дурак",
    "жоп",
    "зло",
    "лох",
    "мертв",
    "мраз",
    "мрак",
    "сатан",
    "смерт",
    "суиц",
    "хер",
    "хрен",
    "черт",
)

NEGATIVE_LATIN = (
    "ass",
    "bad",
    "bum",
    "crap",
    "damn",
    "dead",
    "die",
    "evil",
    "fuck",
    "hell",
    "idiot",
    "kill",
    "nazi",
    "porn",
    "shit",
    "suck",
)

WHOIS_SERVERS = {
    "ru": ("whois.tcinet.ru", ("no entries found",)),
    "com": ("whois.verisign-grs.com", ("no match for",)),
    "app": ("whois.nic.google", ("domain not found", "no match for")),
}


@dataclass
class Candidate:
    name: str
    latin: str
    style: str
    syllables: int
    score: float
    pronounceability: float
    memorability: float
    thematic: bool = False
    uniqueness: float = 0.0
    domains: dict[str, str] = field(default_factory=dict)


def normalize_word(value: str) -> str:
    value = unicodedata.normalize("NFKC", value).lower().replace("ё", "е")
    return re.sub(r"[^a-zа-я]", "", value)


def transliterate(value: str) -> str:
    latin = normalize_word(value).translate(TRANSLIT_TABLE)
    return re.sub(r"[^a-z0-9-]", "", latin)


def count_syllables(value: str) -> int:
    return sum(character in CYRILLIC_VOWELS for character in value.lower())


def phonetic_key(latin: str) -> str:
    key = latin.lower()
    replacements = (
        ("shch", "x"),
        ("sch", "x"),
        ("sh", "x"),
        ("ch", "c"),
        ("zh", "j"),
        ("kh", "h"),
        ("ph", "f"),
        ("th", "t"),
        ("ts", "c"),
        ("ya", "a"),
        ("yu", "u"),
        ("yo", "o"),
    )
    for source, target in replacements:
        key = key.replace(source, target)
    return re.sub(r"[aeiouy]", "", key)


def load_word_list(path: str | None) -> set[str]:
    if not path:
        return set()
    words: set[str] = set()
    with Path(path).open("r", encoding="utf-8-sig") as source:
        for line in source:
            word = normalize_word(line.partition("#")[0])
            if word:
                words.add(word)
    return words


def has_bad_cluster(name: str) -> bool:
    if re.search(r"(.)\1\1", name):
        return True
    if re.search(r"[бвгджзйклмнпрстфхцчшщ]{3}", name):
        return True
    if re.search(r"[аеёиоуыэюя]{3}", name):
        return True
    if name.startswith(("ы", "ь", "ъ", "й")) or name.endswith(("ъ", "ы")):
        return True
    return False


def contains_negative(name: str, latin: str) -> bool:
    if any(fragment in name for fragment in NEGATIVE_CYRILLIC):
        return True
    return any(fragment in latin for fragment in NEGATIVE_LATIN)


def generate_syllable(
    rng: random.Random,
    style: str,
    *,
    allow_coda: bool = False,
    allow_cluster: bool = True,
) -> str:
    parts = STYLE_PARTS[style]
    onsets = parts["onsets"]
    if not allow_cluster:
        onsets = tuple(onset for onset in onsets if len(onset) == 1)
    onset = rng.choice(onsets)
    vowel = rng.choice(parts["vowels"])
    coda = rng.choice(parts["codas"]) if allow_coda and rng.random() < 0.28 else ""
    return onset + vowel + coda


def generate_name(rng: random.Random) -> tuple[str, str, bool]:
    style = rng.choices(("slavic", "church", "greek"), weights=(42, 34, 24))[0]
    target_syllables = rng.choices((2, 3, 4), weights=(20, 57, 23))[0]
    parts = STYLE_PARTS[style]

    # В большинстве вариантов присутствует узнаваемый смысловой корень.
    # Соединительные гласные предотвращают стыки вроде «благп» и «светк».
    use_root = rng.random() < 0.38
    if use_root:
        stem = rng.choices(SEMANTIC_STEMS, weights=SEMANTIC_STEM_WEIGHTS)[0]
        ending = rng.choice(SEMANTIC_ENDINGS)
        if stem[-1:] in CYRILLIC_VOWELS and ending[:1] in CYRILLIC_VOWELS:
            stem = stem[:-1]
        chunks = [stem, ending]
    else:
        ending = rng.choice(parts["endings"])
        ending_syllables = max(1, count_syllables(ending))
        generated_count = max(1, target_syllables - ending_syllables)
        chunks = [
            generate_syllable(
                rng,
                style,
                allow_coda=index == generated_count - 1,
                allow_cluster=index == 0,
            )
            for index in range(generated_count)
        ]
        if chunks[-1][-1:] in CYRILLIC_VOWELS and ending[:1] in CYRILLIC_VOWELS:
            chunks[-1] = chunks[-1][:-1]
        chunks.append(ending)

    name = normalize_word("".join(chunks))
    # Простая стыковка одинаковых букв и слишком тяжёлых границ морфем.
    name = re.sub(r"(.)\1", r"\1", name)
    name = name.replace("йй", "й").replace("ъь", "").replace("ьъ", "")
    return name, style, use_root


def transition_ratio(value: str, vowels: frozenset[str]) -> float:
    if len(value) < 2:
        return 0.0
    kinds = [character in vowels for character in value]
    transitions = sum(left != right for left, right in zip(kinds, kinds[1:]))
    return transitions / (len(value) - 1)


def local_scores(
    name: str,
    latin: str,
    syllables: int,
    thematic: bool,
) -> tuple[float, float, float]:
    cyr_transition = transition_ratio(name, CYRILLIC_VOWELS)
    latin_transition = transition_ratio(latin, LATIN_VOWELS)

    pronounceability = 100.0
    pronounceability -= abs(syllables - 3) * 9
    pronounceability -= abs(cyr_transition - 0.62) * 48
    pronounceability -= max(0, len(latin) - 14) * 3.5
    pronounceability -= 8 if re.search(r"[бвгджзйклмнпрстфхцчшщ]{3}", name) else 0
    pronounceability = max(0.0, min(100.0, pronounceability))

    memorability = 100.0
    memorability -= abs(len(name) - 9) * 3.2
    memorability -= abs(len(latin) - 10) * 2.0
    memorability -= abs(latin_transition - 0.58) * 26
    memorability += 6 if any(root in name for root in SEMANTIC_ROOTS) else 0
    memorability -= max(0, len(set(name)) - 10) * 1.5
    memorability = max(0.0, min(100.0, memorability))

    total = pronounceability * 0.52 + memorability * 0.38
    total += 6 if 7 <= len(name) <= 11 else 0
    total += 4 if 8 <= len(latin) <= 13 else 0
    if thematic:
        relevance = max(
            (value for root, value in THEMATIC_RELEVANCE.items() if name.startswith(root)),
            default=0,
        )
        total += 10 + relevance
    total = min(110.0, total)
    return round(total, 2), round(pronounceability, 2), round(memorability, 2)


def similarity(left: str, right: str) -> float:
    return difflib.SequenceMatcher(None, left, right).ratio()


def brand_uniqueness(latin: str, blocked: Iterable[str]) -> tuple[float, str | None]:
    key = phonetic_key(latin)
    worst_similarity = 0.0
    closest: str | None = None
    for brand in blocked:
        brand_latin = transliterate(brand) or normalize_word(brand)
        if abs(len(latin) - len(brand_latin)) > 5:
            continue
        direct = similarity(latin, brand_latin)
        phonetic = similarity(key, phonetic_key(brand_latin))
        current = max(direct, phonetic * 0.96)
        if current > worst_similarity:
            worst_similarity = current
            closest = brand
    return round((1.0 - worst_similarity) * 100, 2), closest


def trigrams(word: str) -> set[str]:
    padded = f"^{word}$"
    return {padded[index : index + 3] for index in range(max(1, len(padded) - 2))}


def build_trigram_index(words: Iterable[str]) -> dict[str, set[str]]:
    index: dict[str, set[str]] = {}
    for word in words:
        for trigram in trigrams(word):
            index.setdefault(trigram, set()).add(word)
    return index


def resembles_dictionary_word(
    name: str,
    index: dict[str, set[str]],
    threshold: float,
) -> tuple[bool, str | None]:
    possible: set[str] = set()
    for trigram in trigrams(name):
        possible.update(index.get(trigram, ()))
    closest_word: str | None = None
    closest_score = 0.0
    for word in possible:
        if abs(len(name) - len(word)) > 3:
            continue
        current = similarity(name, word)
        if current > closest_score:
            closest_score = current
            closest_word = word
    return closest_score >= threshold, closest_word


def collect_candidates(
    count: int,
    pool_size: int,
    seed: int,
    min_chars: int,
    max_chars: int,
    dictionary: set[str],
) -> tuple[list[Candidate], dict[str, int]]:
    rng = random.Random(seed)
    seen: set[str] = set()
    heap: list[tuple[float, str, Candidate]] = []
    stats = {
        "generated": 0,
        "duplicates": 0,
        "length": 0,
        "syllables": 0,
        "dictionary": 0,
        "unpleasant": 0,
        "accepted": 0,
    }
    exact_words = BUILTIN_WORDS | dictionary

    for _ in range(count):
        stats["generated"] += 1
        name, style, thematic = generate_name(rng)
        if name in seen:
            stats["duplicates"] += 1
            continue
        seen.add(name)

        if not min_chars <= len(name) <= max_chars:
            stats["length"] += 1
            continue
        syllables = count_syllables(name)
        if not 2 <= syllables <= 4:
            stats["syllables"] += 1
            continue
        if name in exact_words:
            stats["dictionary"] += 1
            continue

        latin = transliterate(name)
        if (
            not latin
            or len(latin) > 24
            or has_bad_cluster(name)
            or contains_negative(name, latin)
        ):
            stats["unpleasant"] += 1
            continue

        score, pronounceability, memorability = local_scores(
            name,
            latin,
            syllables,
            thematic,
        )
        candidate = Candidate(
            name=name,
            latin=latin,
            style=style,
            syllables=syllables,
            score=score,
            pronounceability=pronounceability,
            memorability=memorability,
            thematic=thematic,
        )
        item = (score, name, candidate)
        if len(heap) < pool_size:
            heapq.heappush(heap, item)
        elif item[:2] > heap[0][:2]:
            heapq.heapreplace(heap, item)
        stats["accepted"] += 1

    candidates = [item[2] for item in heap]
    candidates.sort(key=lambda candidate: (-candidate.score, candidate.name))
    return candidates, stats


def refine_candidates(
    candidates: list[Candidate],
    top: int,
    blocked: set[str],
    dictionary: set[str],
    dictionary_similarity: float,
    brand_similarity: float,
    family_limit: int,
) -> tuple[list[Candidate], dict[str, int]]:
    refined: list[Candidate] = []
    dictionary_index = build_trigram_index(dictionary | BUILTIN_WORDS)
    stats = {"dictionary_similarity": 0, "brand_similarity": 0}

    for candidate in candidates:
        resembles_word, _ = resembles_dictionary_word(
            candidate.name,
            dictionary_index,
            dictionary_similarity,
        )
        if resembles_word:
            stats["dictionary_similarity"] += 1
            continue

        uniqueness, _ = brand_uniqueness(candidate.latin, blocked | set(BUILTIN_BRANDS))
        candidate.uniqueness = uniqueness
        if uniqueness < (1.0 - brand_similarity) * 100:
            stats["brand_similarity"] += 1
            continue

        candidate.score = round(candidate.score * 0.82 + uniqueness * 0.18, 2)
        refined.append(candidate)

    refined.sort(key=lambda candidate: (-candidate.score, candidate.name))
    diverse: list[Candidate] = []
    deferred: list[Candidate] = []
    family_counts: dict[str, int] = {}
    for candidate in refined:
        family = candidate.name[:2]
        if candidate.thematic:
            family = next(
                (
                    root
                    for root in THEMATIC_RELEVANCE
                    if candidate.name.startswith(root)
                ),
                family,
            )
        if family_counts.get(family, 0) >= family_limit:
            deferred.append(candidate)
            continue
        family_counts[family] = family_counts.get(family, 0) + 1
        diverse.append(candidate)
        if len(diverse) >= top:
            break
    if len(diverse) < top:
        diverse.extend(deferred[: top - len(diverse)])
    return diverse, stats


def fetch_rdap_bootstrap(timeout: float) -> dict[str, str]:
    request = urllib.request.Request(
        "https://data.iana.org/rdap/dns.json",
        headers={"User-Agent": "OrthodoxNamingTool/1.0"},
    )
    with urllib.request.urlopen(request, timeout=timeout) as response:
        payload = json.load(response)
    result: dict[str, str] = {}
    for tlds, urls in payload.get("services", []):
        if not urls:
            continue
        for tld in tlds:
            result[tld.lower()] = urls[0]
    return result


def check_rdap(domain: str, base_url: str, timeout: float) -> str:
    url = base_url.rstrip("/") + "/domain/" + domain
    request = urllib.request.Request(
        url,
        headers={
            "Accept": "application/rdap+json, application/json",
            "User-Agent": "OrthodoxNamingTool/1.0",
        },
    )
    try:
        with urllib.request.urlopen(request, timeout=timeout) as response:
            if response.status == 200:
                return "registered"
    except urllib.error.HTTPError as error:
        if error.code == 404:
            return "available"
        if error.code in (400, 422):
            return "invalid"
        if error.code == 429:
            return "rate_limited"
        return f"unknown_http_{error.code}"
    except (urllib.error.URLError, TimeoutError, socket.timeout):
        return "unknown"
    return "unknown"


def check_whois(domain: str, tld: str, timeout: float) -> str:
    server, available_markers = WHOIS_SERVERS[tld]
    try:
        with socket.create_connection((server, 43), timeout=timeout) as connection:
            connection.sendall((domain + "\r\n").encode("ascii"))
            chunks: list[bytes] = []
            while True:
                chunk = connection.recv(4096)
                if not chunk:
                    break
                chunks.append(chunk)
                if sum(map(len, chunks)) > 256_000:
                    break
        answer = b"".join(chunks).decode("utf-8", errors="ignore").lower()
    except (OSError, TimeoutError, socket.timeout):
        return "unknown"
    if any(marker in answer for marker in available_markers):
        return "available"
    if answer.strip():
        return "registered"
    return "unknown"


def check_domain(
    domain: str,
    tld: str,
    rdap_services: dict[str, str],
    timeout: float,
) -> str:
    if tld in rdap_services:
        status = check_rdap(domain, rdap_services[tld], timeout)
        if status not in {"unknown", "rate_limited"}:
            return status
    return check_whois(domain, tld, timeout)


def check_candidate_domains(
    candidates: list[Candidate],
    limit: int,
    min_free: int,
    delay: float,
    timeout: float,
) -> list[Candidate]:
    try:
        rdap_services = fetch_rdap_bootstrap(timeout)
    except (OSError, ValueError, urllib.error.URLError):
        rdap_services = {}

    selected: list[Candidate] = []
    checked = 0
    for candidate in candidates:
        if checked >= limit:
            break
        checked += 1
        for index, tld in enumerate(("ru", "com", "app")):
            domain = f"{candidate.latin}.{tld}"
            candidate.domains[tld] = check_domain(domain, tld, rdap_services, timeout)
            if delay > 0 and (index < 2 or checked < limit):
                time.sleep(delay)
        free_count = sum(status == "available" for status in candidate.domains.values())
        if free_count >= min_free:
            selected.append(candidate)
    return selected


def write_csv(path: str, candidates: list[Candidate]) -> None:
    target = Path(path)
    target.parent.mkdir(parents=True, exist_ok=True)
    with target.open("w", encoding="utf-8-sig", newline="") as output:
        writer = csv.DictWriter(
            output,
            fieldnames=(
                "name",
                "latin",
                "style",
                "syllables",
                "score",
                "pronounceability",
                "memorability",
                "thematic",
                "uniqueness",
                "ru",
                "com",
                "app",
            ),
        )
        writer.writeheader()
        for candidate in candidates:
            writer.writerow(
                {
                    "name": candidate.name.capitalize(),
                    "latin": candidate.latin,
                    "style": candidate.style,
                    "syllables": candidate.syllables,
                    "score": candidate.score,
                    "pronounceability": candidate.pronounceability,
                    "memorability": candidate.memorability,
                    "thematic": "yes" if candidate.thematic else "no",
                    "uniqueness": candidate.uniqueness,
                    "ru": candidate.domains.get("ru", "unchecked"),
                    "com": candidate.domains.get("com", "unchecked"),
                    "app": candidate.domains.get("app", "unchecked"),
                }
            )


def print_results(candidates: list[Candidate], domain_checks: bool) -> None:
    print()
    print("Лучшие варианты:")
    for index, candidate in enumerate(candidates, 1):
        domain_text = ""
        if domain_checks:
            statuses = ", ".join(
                f".{tld}: {status}" for tld, status in candidate.domains.items()
            )
            domain_text = f" | {statuses}"
        print(
            f"{index:>3}. {candidate.name.capitalize():<18} "
            f"{candidate.latin:<20} score={candidate.score:>5.1f}"
            f"{domain_text}"
        )


def positive_int(value: str) -> int:
    number = int(value)
    if number <= 0:
        raise argparse.ArgumentTypeError("значение должно быть больше нуля")
    return number


def parse_args(argv: list[str] | None = None) -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Офлайн-генератор благозвучных названий с проверкой доменов.",
        formatter_class=argparse.ArgumentDefaultsHelpFormatter,
    )
    parser.add_argument(
        "--count",
        type=positive_int,
        default=500_000,
        help="число генерируемых вариантов",
    )
    parser.add_argument("--top", type=positive_int, default=200, help="число финалистов")
    parser.add_argument(
        "--pool-size",
        type=positive_int,
        default=5000,
        help="размер пула перед точными фильтрами",
    )
    parser.add_argument("--min-chars", type=positive_int, default=7)
    parser.add_argument("--max-chars", type=positive_int, default=14)
    parser.add_argument("--seed", type=int, default=20260628)
    parser.add_argument(
        "--dictionary",
        help="UTF-8 файл со словарём: одно русское слово на строку",
    )
    parser.add_argument(
        "--blocked",
        help="UTF-8 файл брендов и запрещённых названий: одно на строку",
    )
    parser.add_argument(
        "--dictionary-similarity",
        type=float,
        default=0.92,
        help="порог сходства с существующим словом (0..1)",
    )
    parser.add_argument(
        "--brand-similarity",
        type=float,
        default=0.76,
        help="максимально допустимое сходство с брендом (0..1)",
    )
    parser.add_argument(
        "--family-limit",
        type=positive_int,
        default=3,
        help="максимум вариантов с одной основой в финальной выдаче",
    )
    parser.add_argument(
        "--check-domains",
        action="store_true",
        help="проверить .ru, .com и .app у лучших вариантов",
    )
    parser.add_argument(
        "--domain-limit",
        type=positive_int,
        default=40,
        help="сколько финалистов проверять по RDAP/WHOIS",
    )
    parser.add_argument(
        "--min-free-domains",
        type=int,
        choices=(1, 2, 3),
        default=1,
        help="оставить варианты с таким числом свободных доменных зон",
    )
    parser.add_argument(
        "--delay",
        type=float,
        default=0.8,
        help="пауза между доменными запросами, секунд",
    )
    parser.add_argument(
        "--timeout",
        type=float,
        default=8.0,
        help="тайм-аут одного доменного запроса, секунд",
    )
    parser.add_argument(
        "--output",
        default="name_candidates.csv",
        help="выходной CSV-файл",
    )
    args = parser.parse_args(argv)
    if args.min_chars > args.max_chars:
        parser.error("--min-chars не может быть больше --max-chars")
    if not 0.0 < args.dictionary_similarity <= 1.0:
        parser.error("--dictionary-similarity должен быть в диапазоне (0, 1]")
    if not 0.0 < args.brand_similarity <= 1.0:
        parser.error("--brand-similarity должен быть в диапазоне (0, 1]")
    if args.delay < 0 or args.timeout <= 0:
        parser.error("--delay должен быть >= 0, --timeout должен быть > 0")
    return args


def main(argv: list[str] | None = None) -> int:
    args = parse_args(argv)
    dictionary = load_word_list(args.dictionary)
    blocked = load_word_list(args.blocked)

    print(f"Генерация {args.count:,} вариантов…".replace(",", " "))
    started = time.monotonic()
    pool, generation_stats = collect_candidates(
        count=args.count,
        pool_size=max(args.pool_size, args.top * 5),
        seed=args.seed,
        min_chars=args.min_chars,
        max_chars=args.max_chars,
        dictionary=dictionary,
    )
    candidates, refine_stats = refine_candidates(
        candidates=pool,
        top=args.top,
        blocked=blocked,
        dictionary=dictionary,
        dictionary_similarity=args.dictionary_similarity,
        brand_similarity=args.brand_similarity,
        family_limit=args.family_limit,
    )

    if args.check_domains:
        print(
            f"Проверка доменов у {min(args.domain_limit, len(candidates))} "
            "финалистов…"
        )
        candidates = check_candidate_domains(
            candidates=candidates,
            limit=args.domain_limit,
            min_free=args.min_free_domains,
            delay=args.delay,
            timeout=args.timeout,
        )

    write_csv(args.output, candidates)
    elapsed = time.monotonic() - started
    print(
        "Готово за "
        f"{elapsed:.1f} с. Уникальных принятых: "
        f"{generation_stats['accepted']:,}; финалистов: {len(candidates)}."
        .replace(",", " ")
    )
    print(
        "Дополнительные отказы: "
        f"словарное сходство={refine_stats['dictionary_similarity']}, "
        f"сходство с брендами={refine_stats['brand_similarity']}."
    )
    print(f"CSV: {Path(args.output).resolve()}")
    print_results(candidates[: min(30, len(candidates))], args.check_domains)
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except KeyboardInterrupt:
        print("\nОстановлено пользователем.", file=sys.stderr)
        raise SystemExit(130)
