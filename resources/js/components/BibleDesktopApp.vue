<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';

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

type SearchResultDto = {
    verse_text_id: number;
    verse_id: number;
    osis_ref: string | null;
    translation: {
        code: string;
        short_name: string | null;
    };
    book: {
        slug: string;
        osis_code: string | null;
    };
    chapter_number: number;
    verse_number: number;
    text: string;
    snippet: string;
};

type ReaderTab = {
    id: string;
    title: string;
    translationCode: string;
    bookSlug: string;
    chapterNumber: number;
};

type ReaderState = {
    translationCode: string;
    bookSlug: string;
    chapterNumber: number;
    activeTabId?: string;
    tabs?: ReaderTab[];
};

const readerStateKey = 'bible-desktop-reader-state';
const maxReaderTabs = 8;
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
const searchQuery = ref('');
const searchResults = ref<SearchResultDto[]>([]);
const isSearchLoading = ref(false);
const searchError = ref<string | null>(null);
const showSearchResults = ref(false);
const readerTabs = ref<ReaderTab[]>([]);
const activeTabId = ref('');

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
const visibleReaderTabs = computed(() => {
    return readerTabs.value.map((tab) => ({
        ...tab,
        title: tab.id === activeTabId.value ? currentTitle.value : tab.title,
        active: tab.id === activeTabId.value,
    }));
});
const visibleCrossReferences = computed(() => crossReferences.value.slice(0, 12));

function createReaderTab(state: Partial<ReaderTab> = {}): ReaderTab {
    const translationCode = state.translationCode ?? selectedTranslationCode.value;
    const bookSlug = state.bookSlug ?? selectedBookSlug.value;
    const chapterNumber = Math.max(1, Number(state.chapterNumber ?? selectedChapterNumber.value));

    return {
        id: state.id ?? createClientId(),
        title: state.title ?? formatStoredTabTitle(bookSlug, chapterNumber),
        translationCode,
        bookSlug,
        chapterNumber,
    };
}

function createClientId(): string {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    return `tab-${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

function formatStoredTabTitle(bookSlug: string, chapterNumber: number): string {
    const book = books.value.find((candidate) => candidate.slug === bookSlug);

    return `${book?.name ?? bookSlug} ${chapterNumber}`;
}

function normalizeReaderTabs(tabs: unknown): ReaderTab[] {
    if (!Array.isArray(tabs)) {
        return [];
    }

    return tabs
        .map((tab) => {
            if (!tab || typeof tab !== 'object') {
                return null;
            }

            const candidate = tab as Partial<ReaderTab>;

            if (!candidate.translationCode || !candidate.bookSlug || !candidate.chapterNumber) {
                return null;
            }

            return createReaderTab({
                id: typeof candidate.id === 'string' && candidate.id !== '' ? candidate.id : undefined,
                title: typeof candidate.title === 'string' && candidate.title !== '' ? candidate.title : undefined,
                translationCode: candidate.translationCode,
                bookSlug: candidate.bookSlug,
                chapterNumber: Number(candidate.chapterNumber),
            });
        })
        .filter((tab): tab is ReaderTab => tab !== null)
        .slice(0, maxReaderTabs);
}

function activeReaderTab(): ReaderTab | null {
    return readerTabs.value.find((tab) => tab.id === activeTabId.value) ?? readerTabs.value[0] ?? null;
}

function syncActiveTabFromSelection(): void {
    const tab = activeReaderTab();

    if (!tab) {
        return;
    }

    tab.translationCode = selectedTranslationCode.value;
    tab.bookSlug = selectedBookSlug.value;
    tab.chapterNumber = selectedChapterNumber.value;
    tab.title = currentTitle.value;
}

function readReaderState(): ReaderState | null {
    if (typeof window === 'undefined') {
        return null;
    }

    try {
        const value = window.localStorage.getItem(readerStateKey);

        if (!value) {
            return null;
        }

        const parsed = JSON.parse(value) as Partial<ReaderState>;

        if (!parsed.translationCode || !parsed.bookSlug || !parsed.chapterNumber) {
            return null;
        }

        return {
            translationCode: parsed.translationCode,
            bookSlug: parsed.bookSlug,
            chapterNumber: Math.max(1, Number(parsed.chapterNumber)),
            activeTabId: typeof parsed.activeTabId === 'string' ? parsed.activeTabId : undefined,
            tabs: normalizeReaderTabs(parsed.tabs),
        };
    } catch {
        return null;
    }
}

function persistReaderState(): void {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(readerStateKey, JSON.stringify({
        translationCode: selectedTranslationCode.value,
        bookSlug: selectedBookSlug.value,
        chapterNumber: selectedChapterNumber.value,
        activeTabId: activeTabId.value,
        tabs: readerTabs.value,
    }));
}

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

async function loadChapter(targetVerseNumber: number | null = null): Promise<void> {
    if (!selectedTranslationCode.value || !selectedBookSlug.value || selectedChapterNumber.value < 1) {
        return;
    }

    isChapterLoading.value = true;

    try {
        const chapterResponse = await loadJson<ApiResponse<ChapterDto>>(
            `/api/translations/${selectedTranslationCode.value}/books/${selectedBookSlug.value}/chapters/${selectedChapterNumber.value}`,
        );

        currentVerses.value = chapterResponse.data.verses;
        selectedVerse.value = targetVerseNumber === null
            ? currentVerses.value[0] ?? null
            : currentVerses.value.find((verse) => verse.number === targetVerseNumber) ?? currentVerses.value[0] ?? null;
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
    syncActiveTabFromSelection();
    persistReaderState();
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

async function addReaderTab(): Promise<void> {
    if (readerTabs.value.length >= maxReaderTabs) {
        return;
    }

    syncActiveTabFromSelection();
    const tab = createReaderTab({
        translationCode: selectedTranslationCode.value,
        bookSlug: selectedBookSlug.value,
        chapterNumber: selectedChapterNumber.value,
        title: currentTitle.value,
    });

    readerTabs.value.push(tab);
    activeTabId.value = tab.id;
    persistReaderState();
}

async function switchReaderTab(tabId: string): Promise<void> {
    const tab = readerTabs.value.find((candidate) => candidate.id === tabId);

    if (!tab || tab.id === activeTabId.value) {
        return;
    }

    syncActiveTabFromSelection();
    activeTabId.value = tab.id;
    selectedTranslationCode.value = tab.translationCode;
    selectedBookSlug.value = tab.bookSlug;
    selectedChapterNumber.value = tab.chapterNumber;

    await loadBooksForSelectedTranslation();
    await loadChapter();
    persistReaderState();
}

async function closeReaderTab(tabId: string): Promise<void> {
    if (readerTabs.value.length <= 1) {
        return;
    }

    const closingIndex = readerTabs.value.findIndex((tab) => tab.id === tabId);

    if (closingIndex === -1) {
        return;
    }

    const wasActive = readerTabs.value[closingIndex]?.id === activeTabId.value;
    readerTabs.value.splice(closingIndex, 1);

    if (!wasActive) {
        persistReaderState();

        return;
    }

    const nextTab = readerTabs.value[Math.max(0, closingIndex - 1)] ?? readerTabs.value[0];

    if (!nextTab) {
        return;
    }

    activeTabId.value = nextTab.id;
    selectedTranslationCode.value = nextTab.translationCode;
    selectedBookSlug.value = nextTab.bookSlug;
    selectedChapterNumber.value = nextTab.chapterNumber;

    await loadBooksForSelectedTranslation();
    await loadChapter();
    persistReaderState();
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

async function runSearch(): Promise<void> {
    const query = searchQuery.value.trim();

    if (query.length < 2) {
        searchResults.value = [];
        showSearchResults.value = false;

        return;
    }

    isSearchLoading.value = true;
    searchError.value = null;

    try {
        const params = new URLSearchParams({
            q: query,
            translation: selectedTranslationCode.value,
            limit: '8',
        });
        const response = await loadJson<ApiResponse<{ results: SearchResultDto[] }>>(`/api/search/verses?${params.toString()}`);

        searchResults.value = response.data.results;
        showSearchResults.value = true;
    } catch (error) {
        searchResults.value = [];
        showSearchResults.value = true;
        searchError.value = error instanceof Error ? error.message : 'Не удалось выполнить поиск';
    } finally {
        isSearchLoading.value = false;
    }
}

async function openSearchResult(result: SearchResultDto): Promise<void> {
    selectedBookSlug.value = result.book.slug;
    selectedChapterNumber.value = result.chapter_number;
    searchQuery.value = result.osis_ref ?? `${result.book.osis_code ?? result.book.slug}.${result.chapter_number}.${result.verse_number}`;
    showSearchResults.value = false;

    await loadChapter(result.verse_number);
    syncActiveTabFromSelection();
    persistReaderState();
}

onMounted(async () => {
    try {
        const savedState = readReaderState();
        const [languagesResponse, translationsResponse] = await Promise.all([
            loadJson<ApiResponse<LanguageDto[]>>('/api/languages'),
            loadJson<ApiResponse<TranslationDto[]>>('/api/translations'),
        ]);

        languages.value = languagesResponse.data;
        translations.value = translationsResponse.data;
        const defaultTranslationCode = translations.value.find((translation) => translation.is_default)?.code
            ?? translations.value.find((translation) => translation.code === 'L1_RST')?.code
            ?? translations.value[0]?.code
            ?? 'L1_RST';
        selectedTranslationCode.value = savedState && translations.value.some((translation) => translation.code === savedState.translationCode)
            ? savedState.translationCode
            : defaultTranslationCode;
        selectedBookSlug.value = savedState?.bookSlug ?? selectedBookSlug.value;
        selectedChapterNumber.value = savedState?.chapterNumber ?? selectedChapterNumber.value;
        readerTabs.value = savedState?.tabs?.length
            ? savedState.tabs
            : [createReaderTab({
                translationCode: selectedTranslationCode.value,
                bookSlug: selectedBookSlug.value,
                chapterNumber: selectedChapterNumber.value,
            })];
        activeTabId.value = savedState?.activeTabId && readerTabs.value.some((tab) => tab.id === savedState.activeTabId)
            ? savedState.activeTabId
            : readerTabs.value[0]?.id ?? '';
        const initialTab = activeReaderTab();
        if (initialTab && translations.value.some((translation) => translation.code === initialTab.translationCode)) {
            selectedTranslationCode.value = initialTab.translationCode;
            selectedBookSlug.value = initialTab.bookSlug;
            selectedChapterNumber.value = initialTab.chapterNumber;
        }

        await loadBooksForSelectedTranslation();
        if (!books.value.some((book) => book.slug === selectedBookSlug.value)) {
            selectedBookSlug.value = books.value.find((book) => book.slug === 'genesis')?.slug ?? books.value[0]?.slug ?? 'genesis';
        }
        await loadChapter();
        syncActiveTabFromSelection();
        persistReaderState();
    } catch (error) {
        apiError.value = error instanceof Error ? error.message : 'Не удалось загрузить справочник';
    } finally {
        isLoading.value = false;
    }
});

watch([selectedTranslationCode, selectedBookSlug, selectedChapterNumber], () => {
    syncActiveTabFromSelection();
    persistReaderState();
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

            <form class="search" role="search" @submit.prevent="runSearch">
                <button type="submit" aria-label="Найти">S</button>
                <input
                    v-model="searchQuery"
                    type="search"
                    placeholder="Поиск или ссылка"
                    @focus="showSearchResults = searchResults.length > 0 || searchError !== null"
                />
                <div v-if="showSearchResults" class="search-results">
                    <p v-if="isSearchLoading">Ищу...</p>
                    <p v-else-if="searchError">{{ searchError }}</p>
                    <p v-else-if="searchResults.length === 0">Ничего не найдено.</p>
                    <template v-else>
                        <button
                            v-for="result in searchResults"
                            :key="result.verse_text_id"
                            type="button"
                            @click="openSearchResult(result)"
                        >
                            <strong>{{ result.osis_ref ?? `${result.book.osis_code ?? result.book.slug}.${result.chapter_number}.${result.verse_number}` }}</strong>
                            <span>{{ result.snippet }}</span>
                        </button>
                    </template>
                </div>
            </form>

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
            <div
                v-for="tab in visibleReaderTabs"
                :key="tab.id"
                class="reader-tab"
                :class="{ active: tab.active }"
            >
                <button
                    type="button"
                    class="reader-tab-main"
                    @click="switchReaderTab(tab.id)"
                >
                    {{ tab.title }}
                </button>
                <button
                    v-if="readerTabs.length > 1"
                    type="button"
                    class="reader-tab-close"
                    aria-label="Закрыть вкладку"
                    @click="closeReaderTab(tab.id)"
                >
                    X
                </button>
            </div>
            <button
                type="button"
                class="reader-tab-add"
                :disabled="readerTabs.length >= maxReaderTabs"
                aria-label="Открыть новую вкладку"
                @click="addReaderTab"
            >
                +
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
                        @change="() => loadChapter()"
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
