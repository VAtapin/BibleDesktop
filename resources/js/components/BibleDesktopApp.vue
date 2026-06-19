<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';

type LanguageDto = {
    code: string;
    name: string;
    native_name: string;
};

type ReaderBookDto = {
    slug: string;
    name: string;
    short_name: string | null;
    order: number;
    chapters_count: number;
    canonical_book: {
        osis_code: string;
        testament: 'old' | 'new' | 'apocrypha';
        is_deuterocanonical: boolean;
    } | null;
};

type TranslationDto = {
    code: string;
    name: string;
    short_name: string | null;
    language: {
        code: string;
        name: string;
    };
    canon_code: string | null;
    has_strong: boolean;
    is_default: boolean;
};

type ApiResponse<T> = {
    data: T;
};

type Verse = {
    id?: number;
    number: number;
    osis_ref?: string | null;
    text: string;
    has_strong_markup?: boolean;
};

type ChapterDto = {
    translation: {
        code: string;
        name: string;
        short_name: string | null;
    };
    book: {
        slug: string;
        name: string;
        short_name: string | null;
        chapters_count: number;
    };
    chapter: {
        number: number;
        verses_count: number;
    };
    verses: Array<{
        id: number;
        number: number;
        osis_ref: string | null;
        text: string;
        has_strong_markup: boolean;
    }>;
};

type StrongTokenDto = {
    id: number;
    strong_number: string;
    token_order: number;
    surface_text: string | null;
    grammar_code: string | null;
    entry: {
        word: string | null;
        transliteration: string | null;
    };
};

type CrossReferenceDto = {
    id: number;
    type: string;
    source: string;
    target: {
        verse_id: number;
        osis_ref: string | null;
        text: string | null;
    };
};

type Tab = {
    title: string;
    locked?: boolean;
    active?: boolean;
};

const languages = ref<LanguageDto[]>([]);
const translations = ref<TranslationDto[]>([]);
const books = ref<ReaderBookDto[]>([]);
const isLoading = ref(true);
const isBooksLoading = ref(false);
const isChapterLoading = ref(false);
const apiError = ref<string | null>(null);
const selectedTranslationCode = ref('L1_RST');
const selectedBookSlug = ref('genesis');
const selectedChapterNumber = ref(1);
const selectedVerse = ref<Verse | null>(null);
const strongTokens = ref<StrongTokenDto[]>([]);
const crossReferences = ref<CrossReferenceDto[]>([]);
const isStudyLoading = ref(false);
const studyError = ref<string | null>(null);

const tabs: Tab[] = [
    { title: 'Бытие 4', active: true },
    { title: 'Откровение 1' },
    { title: 'Второзаконие 28', locked: true },
];

const demoVerses: Verse[] = [
    {
        number: 1,
        text: 'Откровение Иисуса Христа, которое дал Ему Бог, чтобы показать рабам Своим, чему надлежит быть вскоре.',
    },
    {
        number: 2,
        text: 'Который свидетельствовал слово Божие и свидетельство Иисуса Христа и что он видел.',
    },
    {
        number: 3,
        text: 'Блажен читающий и слушающие слова пророчества сего и соблюдающие написанное в нем; ибо время близко.',
    },
    {
        number: 4,
        text: 'Иоанн семи церквам, находящимся в Асии: благодать вам и мир от Того, Который есть и был и грядет.',
    },
    {
        number: 5,
        text: 'И от Иисуса Христа, Который есть свидетель верный, первенец из мертвых и владыка царей земных.',
    },
    {
        number: 6,
        text: 'И соделавшему нас царями и священниками Богу и Отцу Своему, слава и держава во веки веков, аминь.',
    },
    {
        number: 7,
        text: 'Се, грядет с облаками, и узрит Его всякое око, и те, которые пронзили Его.',
    },
    {
        number: 8,
        text: 'Я есмь Альфа и Омега, начало и конец, говорит Господь, Который есть и был и грядет.',
    },
];
const currentVerses = ref<Verse[]>(demoVerses);

const tools = ['M', 'B', 'R', 'P', 'S#'];

const selectedLanguage = computed(() => languages.value[0]?.native_name ?? 'Русский');
const currentTranslation = computed(() => {
    return translations.value.find((translation) => translation.code === selectedTranslationCode.value) ?? translations.value[0] ?? null;
});

const currentBook = computed(() => {
    return books.value.find((book) => book.slug === selectedBookSlug.value) ?? books.value[0] ?? null;
});
const chapterOptions = computed(() => {
    const count = currentBook.value?.chapters_count ?? 1;

    return Array.from({ length: count }, (_, index) => index + 1);
});
const currentTitle = computed(() => {
    const bookName = currentBook.value?.name ?? currentBook.value?.slug ?? 'Библия';

    return `${bookName} ${selectedChapterNumber.value}`;
});
const visibleCrossReferences = computed(() => crossReferences.value.slice(0, 12));

async function loadJson<T>(url: string): Promise<T> {
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
        },
    });

    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }

    return response.json() as Promise<T>;
}

async function loadChapter(): Promise<void> {
    if (!selectedTranslationCode.value || !selectedBookSlug.value || selectedChapterNumber.value < 1) {
        return;
    }

    isChapterLoading.value = true;

    try {
        const chapterResponse = await loadJson<ApiResponse<ChapterDto>>(
            `/api/translations/${selectedTranslationCode.value}/books/${selectedBookSlug.value}/chapters/${selectedChapterNumber.value}`,
        );

        currentVerses.value = chapterResponse.data.verses;
        selectedVerse.value = currentVerses.value[0] ?? null;
        if (selectedVerse.value?.id) {
            await loadStudyData(selectedVerse.value);
        }
        apiError.value = null;
    } catch (error) {
        currentVerses.value = demoVerses;
        selectedVerse.value = null;
        strongTokens.value = [];
        crossReferences.value = [];
        apiError.value = error instanceof Error ? error.message : 'Не удалось загрузить главу';
    } finally {
        isChapterLoading.value = false;
    }
}

async function loadBooksForSelectedTranslation(): Promise<void> {
    if (!selectedTranslationCode.value) {
        books.value = [];

        return;
    }

    isBooksLoading.value = true;

    try {
        const booksResponse = await loadJson<ApiResponse<{ books: ReaderBookDto[] }>>(
            `/api/translations/${selectedTranslationCode.value}/books`,
        );

        books.value = booksResponse.data.books;

        if (!books.value.some((book) => book.slug === selectedBookSlug.value)) {
            selectedBookSlug.value = books.value[0]?.slug ?? 'genesis';
            selectedChapterNumber.value = 1;
        }

        const maxChapter = currentBook.value?.chapters_count ?? 1;
        if (selectedChapterNumber.value > maxChapter) {
            selectedChapterNumber.value = maxChapter;
        }
    } catch (error) {
        books.value = [];
        apiError.value = error instanceof Error ? error.message : 'Не удалось загрузить книги перевода';
    } finally {
        isBooksLoading.value = false;
    }
}

async function changeTranslation(): Promise<void> {
    selectedChapterNumber.value = 1;
    await loadBooksForSelectedTranslation();
    await loadChapter();
}

function changeBook(): void {
    selectedChapterNumber.value = 1;
    void loadChapter();
}

function goChapter(delta: number): void {
    const nextChapter = selectedChapterNumber.value + delta;
    const maxChapter = currentBook.value?.chapters_count ?? 1;

    if (nextChapter < 1 || nextChapter > maxChapter) {
        return;
    }

    selectedChapterNumber.value = nextChapter;
    void loadChapter();
}

async function loadStudyData(verse: Verse): Promise<void> {
    if (!verse.id) {
        return;
    }

    isStudyLoading.value = true;
    studyError.value = null;

    try {
        const [strongResponse, referencesResponse] = await Promise.all([
            loadJson<ApiResponse<{ tokens: StrongTokenDto[] }>>(`/api/verses/${verse.id}/strong-tokens`),
            loadJson<ApiResponse<{ references: CrossReferenceDto[] }>>(
                `/api/verses/${verse.id}/cross-references?translation=${selectedTranslationCode.value}`,
            ),
        ]);

        strongTokens.value = strongResponse.data.tokens;
        crossReferences.value = referencesResponse.data.references;
    } catch (error) {
        strongTokens.value = [];
        crossReferences.value = [];
        studyError.value = error instanceof Error ? error.message : 'Не удалось загрузить справочник стиха';
    } finally {
        isStudyLoading.value = false;
    }
}

function selectVerse(verse: Verse): void {
    selectedVerse.value = verse;
    void loadStudyData(verse);
}

onMounted(async () => {
    try {
        const [languagesResponse, translationsResponse] = await Promise.all([
            loadJson<ApiResponse<LanguageDto[]>>('/api/languages'),
            loadJson<ApiResponse<TranslationDto[]>>('/api/translations'),
        ]);

        languages.value = languagesResponse.data;
        translations.value = translationsResponse.data;
        selectedTranslationCode.value = translations.value.find((translation) => translation.is_default)?.code
            ?? translations.value.find((translation) => translation.code === 'L1_RST')?.code
            ?? translations.value[0]?.code
            ?? 'L1_RST';

        await loadBooksForSelectedTranslation();
        selectedBookSlug.value = books.value.find((book) => book.slug === 'genesis')?.slug ?? books.value[0]?.slug ?? 'genesis';
        await loadChapter();
    } catch (error) {
        apiError.value = error instanceof Error ? error.message : 'Не удалось загрузить справочник';
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <div class="app-shell">
        <header class="topbar">
            <div class="brand">
                <div class="brand-mark">BD</div>
                <div>
                    <strong>Bible</strong>
                    <span>desktop</span>
                </div>
            </div>

            <label class="search">
                <span>S</span>
                <input type="search" placeholder="Поиск по книгам" />
            </label>

            <div class="profile">
                <div class="profile-text">
                    <strong>Андрей Бутенко</strong>
                    <span>andrey@example.com</span>
                </div>
                <div class="avatar">АБ</div>
            </div>
        </header>

        <section class="workspace-title">
            <span class="muted-icon">Группа</span>
            <strong>Группа 121, анализ текста</strong>
            <button type="button" aria-label="Меню">...</button>
        </section>

        <nav class="tabs" aria-label="Открытые вкладки">
            <button
                v-for="tab in tabs"
                :key="tab.title"
                type="button"
                :class="{ active: tab.active }"
            >
                <span v-if="tab.locked">lock</span>
                {{ tab.title }}
            </button>
        </nav>

        <main class="reader-layout">
            <aside class="tool-rail" aria-label="Инструменты">
                <button v-for="tool in tools" :key="tool" type="button">{{ tool }}</button>
            </aside>

            <section class="reader-panel">
                <div class="reader-toolbar">
                    <button type="button" class="bookmark" aria-label="Закладка">+</button>
                    <select
                        v-model="selectedTranslationCode"
                        aria-label="Перевод"
                        @change="changeTranslation"
                    >
                        <option
                            v-for="translation in translations"
                            :key="translation.code"
                            :value="translation.code"
                        >
                            {{ translation.short_name ?? translation.name }}
                        </option>
                        <option v-if="translations.length === 0" value="L1_RST">RST</option>
                    </select>
                    <select
                        v-model="selectedBookSlug"
                        aria-label="Книга"
                        @change="changeBook"
                    >
                        <template v-if="isLoading || isBooksLoading">
                            <option>Загрузка книг...</option>
                        </template>
                        <template v-else>
                            <option
                                v-for="book in books"
                                :key="book.slug"
                                :value="book.slug"
                            >
                                {{ book.name }} ({{ book.chapters_count }})
                            </option>
                            <option v-if="books.length === 0" value="genesis">Книги ещё не импортированы</option>
                        </template>
                    </select>
                    <select
                        v-model.number="selectedChapterNumber"
                        class="chapter-select"
                        aria-label="Глава"
                        @change="loadChapter"
                    >
                        <option
                            v-for="chapter in chapterOptions"
                            :key="chapter"
                            :value="chapter"
                        >
                            {{ chapter }}
                        </option>
                    </select>
                    <div class="reader-actions">
                        <button type="button" aria-label="Strong">S#</button>
                        <button type="button" aria-label="Печать">P</button>
                        <button type="button" aria-label="Закрыть">X</button>
                    </div>
                </div>

                <article class="chapter">
                    <p v-if="isChapterLoading" class="reader-status">
                        Загружаю главу...
                    </p>
                    <p v-else-if="currentVerses.length === 0" class="reader-status">
                        В этой главе пока нет импортированного текста.
                    </p>
                    <p
                        v-for="verse in currentVerses"
                        :key="verse.id ?? verse.number"
                        :class="{ selected: verse.id === selectedVerse?.id }"
                    >
                        <button
                            type="button"
                            class="verse-number"
                            @click="selectVerse(verse)"
                        >
                            {{ verse.number }}
                        </button>
                        <span @click="selectVerse(verse)">{{ verse.text }}</span>
                    </p>
                </article>

                <div class="reader-footer">
                    <button
                        type="button"
                        :disabled="selectedChapterNumber <= 1"
                        @click="goChapter(-1)"
                    >
                        Назад
                    </button>
                    <span>{{ currentTitle }}</span>
                    <button
                        type="button"
                        :disabled="selectedChapterNumber >= (currentBook?.chapters_count ?? 1)"
                        @click="goChapter(1)"
                    >
                        Далее
                    </button>
                </div>
            </section>

            <aside class="analysis-panel">
                <header>
                    <h2>Справочник</h2>
                    <button type="button" aria-label="Обновить">R</button>
                </header>

                <div class="analysis-tabs">
                    <button type="button" class="active">Strong</button>
                    <button type="button">Ссылки</button>
                </div>

                <section class="note">
                    <div class="note-meta">
                        <div class="avatar small">{{ selectedLanguage.slice(0, 2).toUpperCase() }}</div>
                        <strong>{{ selectedVerse?.osis_ref ?? currentTitle }}</strong>
                        <span>{{ currentTranslation?.short_name ?? currentTranslation?.code ?? 'RST' }}</span>
                    </div>
                    <p v-if="isLoading">Загружаю языки и канон из API.</p>
                    <p v-else-if="apiError">API: {{ apiError }}</p>
                    <p v-else-if="studyError">API: {{ studyError }}</p>
                    <p v-else-if="isStudyLoading">Загружаю справочник стиха.</p>
                    <p v-else>
                        Strong: {{ strongTokens.length }}. Ссылки: {{ crossReferences.length }}.
                    </p>
                </section>

                <section class="events">
                    <h3>Strong</h3>
                    <div class="study-list">
                        <a
                            v-for="token in strongTokens"
                            :key="token.id"
                            :href="`/api/strong/${token.strong_number}`"
                            target="_blank"
                            rel="noreferrer"
                        >
                            <strong>{{ token.strong_number }}</strong>
                            <span>{{ token.surface_text ?? token.entry.transliteration ?? token.entry.word ?? '' }}</span>
                        </a>
                        <p v-if="!isStudyLoading && strongTokens.length === 0">Нет Strong-разметки.</p>
                    </div>

                    <h3>Перекрёстные ссылки</h3>
                    <div class="reference-list">
                        <button
                            v-for="reference in visibleCrossReferences"
                            :key="reference.id"
                            type="button"
                        >
                            <strong>{{ reference.target.osis_ref }}</strong>
                            <span>{{ reference.target.text ?? reference.type }}</span>
                        </button>
                        <p v-if="!isStudyLoading && crossReferences.length === 0">Нет ссылок.</p>
                    </div>
                </section>

                <form class="comment-form">
                    <textarea placeholder="Напишите свой комментарий"></textarea>
                    <button type="button">Написать</button>
                </form>
            </aside>
        </main>

        <footer class="footerbar">
            <button type="button">{{ selectedLanguage }}</button>
            <nav>
                <a href="#">Информация</a>
                <a href="#">О проекте</a>
                <a href="#">Разработчики</a>
                <a href="#">Контакты</a>
            </nav>
        </footer>
    </div>
</template>
