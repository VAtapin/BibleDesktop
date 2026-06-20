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
    strong_tokens?: StrongTokenDto[];
};

type VerseTextPart = {
    key: string;
    text: string;
    strongTokens: StrongTokenDto[];
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
        strong_tokens?: StrongTokenDto[];
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

type StrongEntryDto = {
    id: number;
    number: string;
    word: string | null;
    transliteration: string | null;
    pronunciation: string | null;
    content: string | null;
    raw_content: string | null;
    lexicon: {
        code: string;
        name: string;
        language: string;
    };
};

type CrossReferenceDto = {
    id: number;
    type: string;
    source: string;
    target: {
        verse_id: number;
        osis_ref: string | null;
        reference?: string;
        book_slug?: string;
        book_name?: string | null;
        book_short_name?: string | null;
        chapter_number: number;
        verse_number: number;
        text: string | null;
    };
};

type NoteDto = {
    id: number;
    body: string;
    visibility: string;
    created_at: string;
    updated_at: string;
};

type SearchResultDto = {
    verse_text_id: number;
    verse_id: number;
    osis_ref: string | null;
    reference?: string;
    translation: {
        code: string;
        short_name: string | null;
    };
    book: {
        slug: string;
        osis_code: string | null;
        name?: string | null;
        short_name?: string | null;
    };
    chapter_number: number;
    verse_number: number;
    text: string;
    snippet: string;
    snippet_segments?: Array<{
        text: string;
        match: boolean;
    }>;
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
    compareTranslationCode?: string;
    bookSlug: string;
    chapterNumber: number;
    activeTabId?: string;
    tabs?: ReaderTab[];
};

type BookmarkItem = {
    id: string;
    reference: string;
    translationCode: string;
    bookSlug: string;
    chapterNumber: number;
    verseNumber: number;
    text: string;
    createdAt: string;
};

const readerStateKey = 'bible-desktop-reader-state';
const bookmarkStateKey = 'bible-desktop-bookmarks';
const maxReaderTabs = 8;
const languages = ref<LanguageDto[]>([]);
const translations = ref<TranslationDto[]>([]);
const books = ref<ReaderBookDto[]>([]);
const isLoading = ref(true);
const isBooksLoading = ref(false);
const isChapterLoading = ref(false);
const apiError = ref<string | null>(null);
const selectedTranslationCode = ref('L1_RST');
const compareTranslationCode = ref('');
const selectedBookSlug = ref('genesis');
const selectedChapterNumber = ref(1);
const selectedVerse = ref<Verse | null>(null);
const strongTokens = ref<StrongTokenDto[]>([]);
const selectedStrongEntry = ref<StrongEntryDto | null>(null);
const crossReferences = ref<CrossReferenceDto[]>([]);
const verseNotes = ref<NoteDto[]>([]);
const noteBody = ref('');
const isStudyLoading = ref(false);
const isNotesLoading = ref(false);
const studyError = ref<string | null>(null);
const noteError = ref<string | null>(null);
const searchQuery = ref('');
const searchResults = ref<SearchResultDto[]>([]);
const isSearchLoading = ref(false);
const searchError = ref<string | null>(null);
const showSearchResults = ref(false);
const searchScope = ref('canonical');
const activeLeftPanel = ref<'search' | 'bookmarks' | null>(null);
const advancedSearchQuery = ref('');
const advancedSearchScope = ref('canonical');
const advancedSearchMatch = ref<'all_words' | 'phrase' | 'partial' | 'fuzzy'>('all_words');
const advancedSearchResults = ref<SearchResultDto[]>([]);
const isAdvancedSearchLoading = ref(false);
const advancedSearchError = ref<string | null>(null);
const bookmarks = ref<BookmarkItem[]>([]);
const highlightedVerseNumbers = ref<number[]>([]);
const readerTabs = ref<ReaderTab[]>([]);
const activeTabId = ref('');
const activeStudyTab = ref<'strong' | 'references' | 'notes'>('strong');
const verseMenu = ref<{ verse: Verse; x: number; y: number } | null>(null);
const verseActionMessage = ref('');
const showStrongNumbers = ref(false);
let verseActionMessageTimer: number | undefined;

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
const compareVerses = ref<Verse[]>([]);
const isCompareLoading = ref(false);
const compareError = ref<string | null>(null);

const tools = [
    { id: 'search', label: 'S', title: 'Расширенный поиск' },
    { id: 'bookmarks', label: 'B', title: 'Закладки' },
    { id: 'references', label: 'R', title: 'Справочник' },
    { id: 'print', label: 'P', title: 'Печать' },
    { id: 'strong', label: 'S#', title: 'Strong' },
] as const;

const selectedLanguage = computed(() => languages.value[0]?.native_name ?? 'Русский');
const currentTranslation = computed(() => {
    return translations.value.find((translation) => translation.code === selectedTranslationCode.value) ?? translations.value[0] ?? null;
});
const compareTranslation = computed(() => {
    return translations.value.find((translation) => translation.code === compareTranslationCode.value) ?? null;
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
const selectedVerseReference = computed(() => {
    return selectedVerse.value ? verseReference(selectedVerse.value) : currentTitle.value;
});
const compareVerseByNumber = computed(() => {
    return new Map(compareVerses.value.map((verse) => [verse.number, verse]));
});

function translationLabel(translation: TranslationDto | null | undefined): string {
    if (!translation) {
        return 'Перевод';
    }

    return translation.name || translation.short_name || translation.code;
}

function shortTranslationLabel(translation: TranslationDto | null | undefined): string {
    if (!translation) {
        return 'Перевод';
    }

    const label = translation.short_name || translation.name || translation.code;

    if (label.length <= 12) {
        return label;
    }

    const abbreviation = label
        .replace(/[()]/g, ' ')
        .split(/[.\s]+/u)
        .filter((word) => word.length > 0 && !['с', 'и', 'в', 'на', 'по'].includes(word.toLocaleLowerCase('ru-RU')))
        .map((word) => word[0]?.toLocaleUpperCase('ru-RU') ?? '')
        .join('')
        .slice(0, 5);

    return abbreviation || label.slice(0, 12);
}

function bookDisplayName(book: ReaderBookDto | null | undefined): string {
    return book?.name || book?.short_name || book?.slug || 'Библия';
}

function normalizeStrongSurface(value: string | null | undefined): string {
    return (value ?? '')
        .toLocaleLowerCase('ru-RU')
        .replace(/ё/g, 'е')
        .replace(/[^\p{L}\p{N}]+/gu, '');
}

function verseTextParts(verse: Verse): VerseTextPart[] {
    const strongTokens = verse.strong_tokens ?? [];
    const parts = verse.text.match(/[\p{L}\p{N}]+|[^\p{L}\p{N}]+/gu) ?? [verse.text];
    let strongIndex = 0;

    return parts.map((text, index) => {
        const matchedTokens: StrongTokenDto[] = [];
        const normalizedText = normalizeStrongSurface(text);

        if (normalizedText !== '') {
            while (
                strongIndex < strongTokens.length
                && normalizeStrongSurface(strongTokens[strongIndex]?.surface_text) === normalizedText
            ) {
                matchedTokens.push(strongTokens[strongIndex]);
                strongIndex++;
            }
        }

        return {
            key: `${verse.id ?? verse.number}-${index}`,
            text,
            strongTokens: matchedTokens,
        };
    });
}

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
            compareTranslationCode: typeof parsed.compareTranslationCode === 'string' ? parsed.compareTranslationCode : undefined,
            bookSlug: parsed.bookSlug,
            chapterNumber: Math.max(1, Number(parsed.chapterNumber)),
            activeTabId: typeof parsed.activeTabId === 'string' ? parsed.activeTabId : undefined,
            tabs: normalizeReaderTabs(parsed.tabs),
        };
    } catch {
        return null;
    }
}

function readBookmarks(): BookmarkItem[] {
    if (typeof window === 'undefined') {
        return [];
    }

    try {
        const value = window.localStorage.getItem(bookmarkStateKey);
        const parsed = value ? JSON.parse(value) : [];

        return Array.isArray(parsed) ? parsed.filter((item): item is BookmarkItem => {
            return item
                && typeof item.id === 'string'
                && typeof item.bookSlug === 'string'
                && typeof item.chapterNumber === 'number'
                && typeof item.verseNumber === 'number';
        }) : [];
    } catch {
        return [];
    }
}

function persistBookmarks(): void {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(bookmarkStateKey, JSON.stringify(bookmarks.value));
}

function readUrlState(): Partial<ReaderState> & { verses?: number[] } {
    if (typeof window === 'undefined') {
        return {};
    }

    const params = new URLSearchParams(window.location.search);
    const verses = (params.get('verses') ?? '')
        .split(',')
        .map((value) => Number(value))
        .filter((value) => Number.isInteger(value) && value > 0);

    return {
        translationCode: params.get('translation') || undefined,
        bookSlug: params.get('book') || undefined,
        chapterNumber: params.get('chapter') ? Number(params.get('chapter')) : undefined,
        verses,
    };
}

function persistReaderState(): void {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(readerStateKey, JSON.stringify({
        translationCode: selectedTranslationCode.value,
        compareTranslationCode: compareTranslationCode.value,
        bookSlug: selectedBookSlug.value,
        chapterNumber: selectedChapterNumber.value,
        activeTabId: activeTabId.value,
        tabs: readerTabs.value,
    }));
}

function toggleLeftPanel(panel: 'search' | 'bookmarks'): void {
    activeLeftPanel.value = activeLeftPanel.value === panel ? null : panel;
}

function handleToolClick(toolId: string): void {
    if (toolId === 'search') {
        toggleLeftPanel('search');
        return;
    }

    if (toolId === 'bookmarks') {
        toggleLeftPanel('bookmarks');
        return;
    }

    if (toolId === 'strong') {
        showStrongNumbers.value = !showStrongNumbers.value;
    }
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

async function postJson<T>(url: string, payload: Record<string, unknown>): Promise<T> {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
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
        if (highlightedVerseNumbers.value.length > 0) {
            selectedVerse.value = currentVerses.value.find((verse) => verse.number === highlightedVerseNumbers.value[0]) ?? selectedVerse.value;
        }
        if (selectedVerse.value?.id) {
            await loadStudyData(selectedVerse.value);
        }
        await loadCompareChapter();
        apiError.value = null;
    } catch (error) {
        currentVerses.value = demoVerses;
        compareVerses.value = [];
        selectedVerse.value = null;
        strongTokens.value = [];
        crossReferences.value = [];
        apiError.value = error instanceof Error ? error.message : 'Не удалось загрузить главу';
    } finally {
        isChapterLoading.value = false;
    }
}

async function loadCompareChapter(): Promise<void> {
    compareVerses.value = [];
    compareError.value = null;

    if (!compareTranslationCode.value || compareTranslationCode.value === selectedTranslationCode.value) {
        return;
    }

    isCompareLoading.value = true;

    try {
        const chapterResponse = await loadJson<ApiResponse<ChapterDto>>(
            `/api/translations/${compareTranslationCode.value}/books/${selectedBookSlug.value}/chapters/${selectedChapterNumber.value}`,
        );

        compareVerses.value = chapterResponse.data.verses;
    } catch (error) {
        compareError.value = error instanceof Error ? error.message : 'Не удалось загрузить параллельный перевод';
    } finally {
        isCompareLoading.value = false;
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
    if (compareTranslationCode.value === selectedTranslationCode.value) {
        compareTranslationCode.value = '';
    }

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

function changeCompareTranslation(): void {
    void loadCompareChapter();
    persistReaderState();
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

function addBookmark(): void {
    const verse = selectedVerse.value;

    if (!verse?.id) {
        showVerseActionMessage('Выберите стих');
        return;
    }

    const bookmark: BookmarkItem = {
        id: `${selectedTranslationCode.value}:${selectedBookSlug.value}:${selectedChapterNumber.value}:${verse.number}`,
        reference: verseReference(verse),
        translationCode: selectedTranslationCode.value,
        bookSlug: selectedBookSlug.value,
        chapterNumber: selectedChapterNumber.value,
        verseNumber: verse.number,
        text: verse.text,
        createdAt: new Date().toISOString(),
    };

    bookmarks.value = [
        bookmark,
        ...bookmarks.value.filter((item) => item.id !== bookmark.id),
    ].slice(0, 100);
    persistBookmarks();
    activeLeftPanel.value = 'bookmarks';
    showVerseActionMessage('Закладка добавлена');
}

async function openBookmark(bookmark: BookmarkItem): Promise<void> {
    selectedTranslationCode.value = bookmark.translationCode;
    selectedBookSlug.value = bookmark.bookSlug;
    selectedChapterNumber.value = bookmark.chapterNumber;
    highlightedVerseNumbers.value = [bookmark.verseNumber];

    await loadBooksForSelectedTranslation();
    await loadChapter(bookmark.verseNumber);
    syncActiveTabFromSelection();
    persistReaderState();
}

function removeBookmark(bookmark: BookmarkItem): void {
    bookmarks.value = bookmarks.value.filter((item) => item.id !== bookmark.id);
    persistBookmarks();
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
        strongTokens.value = [];
        selectedStrongEntry.value = null;
        crossReferences.value = [];
        verseNotes.value = [];
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
        selectedStrongEntry.value = null;
        crossReferences.value = referencesResponse.data.references;
        await loadVerseNotes(verse.id);
    } catch (error) {
        strongTokens.value = [];
        selectedStrongEntry.value = null;
        crossReferences.value = [];
        verseNotes.value = [];
        studyError.value = error instanceof Error ? error.message : 'Не удалось загрузить справочник стиха';
    } finally {
        isStudyLoading.value = false;
    }
}

async function selectStrongToken(token: StrongTokenDto): Promise<void> {
    activeStudyTab.value = 'strong';
    studyError.value = null;

    try {
        const response = await loadJson<ApiResponse<StrongEntryDto>>(`/api/strong/${token.strong_number}`);
        selectedStrongEntry.value = response.data;
    } catch (error) {
        selectedStrongEntry.value = null;
        studyError.value = error instanceof Error ? error.message : 'Не удалось загрузить номер Strong';
    }
}

async function selectInlineStrongToken(verse: Verse, token: StrongTokenDto): Promise<void> {
    const previousVerseId = selectedVerse.value?.id;

    selectedVerse.value = verse;
    highlightedVerseNumbers.value = [verse.number];
    activeStudyTab.value = 'strong';

    if (verse.id && verse.id !== previousVerseId) {
        await loadStudyData(verse);
    }

    await selectStrongToken(token);
}

async function loadVerseNotes(verseId: number): Promise<void> {
    isNotesLoading.value = true;
    noteError.value = null;

    try {
        const response = await loadJson<ApiResponse<{ notes: NoteDto[] }>>(`/api/verses/${verseId}/notes`);
        verseNotes.value = response.data.notes;
    } catch (error) {
        verseNotes.value = [];
        noteError.value = error instanceof Error ? error.message : 'Не удалось загрузить заметки';
    } finally {
        isNotesLoading.value = false;
    }
}

async function submitVerseNote(): Promise<void> {
    const body = noteBody.value.trim();

    if (!selectedVerse.value?.id || body.length === 0) {
        return;
    }

    isNotesLoading.value = true;
    noteError.value = null;

    try {
        await postJson<ApiResponse<{ note: NoteDto }>>(`/api/verses/${selectedVerse.value.id}/notes`, { body });
        noteBody.value = '';
        await loadVerseNotes(selectedVerse.value.id);
    } catch (error) {
        noteError.value = error instanceof Error ? error.message : 'Не удалось сохранить заметку';
    } finally {
        isNotesLoading.value = false;
    }
}

function selectVerse(verse: Verse): void {
    selectedVerse.value = verse;
    highlightedVerseNumbers.value = [verse.number];
    verseMenu.value = null;
    void loadStudyData(verse);
}

function verseReference(verse: Verse): string {
    return `${bookDisplayName(currentBook.value)} ${selectedChapterNumber.value}:${verse.number}`;
}

function searchResultReference(result: SearchResultDto): string {
    return result.reference
        ?? `${result.book.name || result.book.short_name || result.book.osis_code || result.book.slug} ${result.chapter_number}:${result.verse_number}`;
}

function crossReferenceLabel(reference: CrossReferenceDto): string {
    return reference.target.reference
        ?? `${reference.target.book_name || reference.target.book_short_name || reference.target.osis_ref || 'Библия'} ${reference.target.chapter_number}:${reference.target.verse_number}`;
}

function versePermalink(verse: Verse): string {
    const url = new URL(window.location.href);
    url.search = new URLSearchParams({
        translation: selectedTranslationCode.value,
        book: selectedBookSlug.value,
        chapter: String(selectedChapterNumber.value),
        verses: String(verse.number),
    }).toString();

    return url.toString();
}

function openVerseMenu(event: MouseEvent, verse: Verse): void {
    event.preventDefault();
    selectedVerse.value = verse;
    verseMenu.value = {
        verse,
        x: Math.min(event.clientX, window.innerWidth - 220),
        y: Math.min(event.clientY, window.innerHeight - 150),
    };

    if (verse.id) {
        void loadStudyData(verse);
    }
}

function closeVerseMenu(): void {
    verseMenu.value = null;
}

async function copyToClipboard(value: string): Promise<void> {
    if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(value);
        return;
    }

    const textarea = document.createElement('textarea');
    textarea.value = value;
    textarea.setAttribute('readonly', 'true');
    textarea.style.position = 'fixed';
    textarea.style.left = '-9999px';
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
}

async function copyVerseReference(verse: Verse): Promise<void> {
    await copyToClipboard(versePermalink(verse));
    showVerseActionMessage('Ссылка скопирована');
    closeVerseMenu();
}

async function copyVerseText(verse: Verse): Promise<void> {
    await copyToClipboard(`${verseReference(verse)} ${verse.text}`);
    showVerseActionMessage('Стих скопирован');
    closeVerseMenu();
}

function openVerseStudy(verse: Verse): void {
    selectVerse(verse);
    activeStudyTab.value = 'strong';
    showVerseActionMessage('Справочник открыт');
}

function showVerseActionMessage(message: string): void {
    verseActionMessage.value = message;

    if (verseActionMessageTimer) {
        window.clearTimeout(verseActionMessageTimer);
    }

    verseActionMessageTimer = window.setTimeout(() => {
        verseActionMessage.value = '';
    }, 1800);
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
            scope: searchScope.value === 'apocrypha' ? 'all' : searchScope.value,
        });
        if (searchScope.value === 'apocrypha') {
            params.set('apocrypha', '1');
        } else {
            params.set('canonical', '1');
        }
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

async function runAdvancedSearch(): Promise<void> {
    const query = advancedSearchQuery.value.trim();

    if (query.length < 2) {
        advancedSearchResults.value = [];
        advancedSearchError.value = 'Введите минимум 2 символа';

        return;
    }

    isAdvancedSearchLoading.value = true;
    advancedSearchError.value = null;

    try {
        const params = new URLSearchParams({
            q: query,
            translation: selectedTranslationCode.value,
            limit: '30',
            match: advancedSearchMatch.value,
            scope: advancedSearchScope.value === 'apocrypha' ? 'all' : advancedSearchScope.value,
        });

        if (advancedSearchScope.value === 'apocrypha') {
            params.set('apocrypha', '1');
        } else {
            params.set('canonical', '1');
        }

        const response = await loadJson<ApiResponse<{ results: SearchResultDto[] }>>(`/api/search/verses?${params.toString()}`);
        advancedSearchResults.value = response.data.results;
    } catch (error) {
        advancedSearchResults.value = [];
        advancedSearchError.value = error instanceof Error ? error.message : 'Не удалось выполнить поиск';
    } finally {
        isAdvancedSearchLoading.value = false;
    }
}

async function openSearchResult(result: SearchResultDto): Promise<void> {
    selectedBookSlug.value = result.book.slug;
    selectedChapterNumber.value = result.chapter_number;
    highlightedVerseNumbers.value = [result.verse_number];
    searchQuery.value = searchResultReference(result);
    showSearchResults.value = false;

    await loadChapter(result.verse_number);
    syncActiveTabFromSelection();
    persistReaderState();
}

async function openAdvancedSearchResult(result: SearchResultDto): Promise<void> {
    await openSearchResult(result);
}

async function openCrossReference(reference: CrossReferenceDto): Promise<void> {
    const bookSlug = reference.target.book_slug;

    if (!bookSlug) {
        return;
    }

    syncActiveTabFromSelection();

    const tab = createReaderTab({
        translationCode: selectedTranslationCode.value,
        bookSlug,
        chapterNumber: reference.target.chapter_number,
        title: crossReferenceLabel(reference),
    });

    if (readerTabs.value.length < maxReaderTabs) {
        readerTabs.value.push(tab);
        activeTabId.value = tab.id;
    }

    selectedBookSlug.value = bookSlug;
    selectedChapterNumber.value = reference.target.chapter_number;
    highlightedVerseNumbers.value = [reference.target.verse_number];

    await loadBooksForSelectedTranslation();
    await loadChapter(reference.target.verse_number);
    syncActiveTabFromSelection();
    persistReaderState();
}

onMounted(async () => {
    try {
        const savedState = readReaderState();
        const urlState = readUrlState();
        bookmarks.value = readBookmarks();
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
        selectedTranslationCode.value = urlState.translationCode && translations.value.some((translation) => translation.code === urlState.translationCode)
            ? urlState.translationCode
            : savedState && translations.value.some((translation) => translation.code === savedState.translationCode)
                ? savedState.translationCode
            : defaultTranslationCode;
        compareTranslationCode.value = savedState?.compareTranslationCode && translations.value.some((translation) => translation.code === savedState.compareTranslationCode)
            ? savedState.compareTranslationCode
            : '';
        selectedBookSlug.value = urlState.bookSlug ?? savedState?.bookSlug ?? selectedBookSlug.value;
        selectedChapterNumber.value = urlState.chapterNumber ?? savedState?.chapterNumber ?? selectedChapterNumber.value;
        highlightedVerseNumbers.value = urlState.verses ?? [];
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
        await loadChapter(highlightedVerseNumbers.value[0] ?? null);
        syncActiveTabFromSelection();
        persistReaderState();
    } catch (error) {
        apiError.value = error instanceof Error ? error.message : 'Не удалось загрузить справочник';
    } finally {
        isLoading.value = false;
    }
});

watch([selectedTranslationCode, compareTranslationCode, selectedBookSlug, selectedChapterNumber], () => {
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
                <select v-model="searchScope" aria-label="Фильтр поиска">
                    <option value="canonical">Канон</option>
                    <option value="old">Ветхий Завет</option>
                    <option value="new">Новый Завет</option>
                    <option value="psalms">Псалтирь</option>
                    <option value="apocrypha">Апокрифы</option>
                </select>
                <div v-if="showSearchResults" class="search-results">
                    <p v-if="isSearchLoading">Ищу...</p>
                    <p v-else-if="searchError">{{ searchError }}</p>
                    <template v-else>
                        <button type="button" class="advanced-search-open" @click="activeLeftPanel = 'search'; advancedSearchQuery = searchQuery; showSearchResults = false">
                            <strong>Расширенный поиск</strong>
                            <span>Фильтры, точность, часть слова, опечатки</span>
                        </button>
                        <p v-if="searchResults.length === 0">Ничего не найдено.</p>
                        <template v-else>
                            <button
                                v-for="result in searchResults"
                                :key="result.verse_text_id"
                                type="button"
                                @click="openSearchResult(result)"
                            >
                                <strong>{{ searchResultReference(result) }}</strong>
                                <span>
                                    <template
                                        v-for="(segment, index) in result.snippet_segments ?? [{ text: result.snippet, match: false }]"
                                        :key="`${result.verse_text_id}-${index}`"
                                    >
                                        <mark v-if="segment.match">{{ segment.text }}</mark>
                                        <template v-else>{{ segment.text }}</template>
                                    </template>
                                </span>
                            </button>
                        </template>
                    </template>
                </div>
            </form>

            <div class="profile">
                <div class="profile-text">
                    <strong>Войти</strong>
                    <span>Личный кабинет</span>
                </div>
                <div class="avatar">В</div>
            </div>
        </header>

        <section class="workspace-title">
            <span class="muted-icon">Чтение</span>
            <strong>{{ currentTitle }}</strong>
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

        <main class="reader-layout" :class="{ 'has-left-panel': activeLeftPanel !== null }">
            <aside class="tool-rail" aria-label="Инструменты">
                <button
                    v-for="tool in tools"
                    :key="tool.id"
                    type="button"
                    :title="tool.title"
                    :class="{ active: activeLeftPanel === tool.id || (tool.id === 'strong' && showStrongNumbers) }"
                    @click="handleToolClick(tool.id)"
                >
                    {{ tool.label }}
                </button>
            </aside>

            <aside v-if="activeLeftPanel" class="left-panel">
                <header>
                    <h2>{{ activeLeftPanel === 'search' ? 'Расширенный поиск' : 'Закладки' }}</h2>
                    <button type="button" aria-label="Закрыть" @click="activeLeftPanel = null">X</button>
                </header>

                <form v-if="activeLeftPanel === 'search'" class="advanced-search-panel" @submit.prevent="runAdvancedSearch">
                    <input v-model="advancedSearchQuery" type="search" placeholder="Введите слово или фразу" />
                    <select v-model="advancedSearchMatch">
                        <option value="all_words">Все слова</option>
                        <option value="phrase">Точная фраза</option>
                        <option value="partial">Часть слова</option>
                        <option value="fuzzy">С опечатками</option>
                    </select>
                    <select v-model="advancedSearchScope">
                        <option value="canonical">Канон</option>
                        <option value="old">Ветхий Завет</option>
                        <option value="new">Новый Завет</option>
                        <option value="psalms">Псалтирь</option>
                        <option value="apocrypha">Апокрифы</option>
                    </select>
                    <button type="submit" :disabled="isAdvancedSearchLoading">
                        {{ isAdvancedSearchLoading ? 'Ищу...' : 'Найти' }}
                    </button>
                    <p v-if="advancedSearchError">{{ advancedSearchError }}</p>
                    <div class="advanced-search-results">
                        <button
                            v-for="result in advancedSearchResults"
                            :key="result.verse_text_id"
                            type="button"
                            @click="openAdvancedSearchResult(result)"
                        >
                            <strong>{{ searchResultReference(result) }}</strong>
                            <span>{{ result.snippet || result.text }}</span>
                        </button>
                        <p v-if="!isAdvancedSearchLoading && !advancedSearchError && advancedSearchResults.length === 0">Результатов пока нет.</p>
                    </div>
                </form>

                <section v-else class="bookmark-panel">
                    <button type="button" class="bookmark-add" @click="addBookmark">Добавить выбранный стих</button>
                    <div class="bookmark-list">
                        <article v-for="bookmark in bookmarks" :key="bookmark.id">
                            <button type="button" @click="openBookmark(bookmark)">
                                <strong>{{ bookmark.reference }}</strong>
                                <span>{{ bookmark.text }}</span>
                            </button>
                            <button type="button" aria-label="Удалить" @click="removeBookmark(bookmark)">X</button>
                        </article>
                        <p v-if="bookmarks.length === 0">Закладок пока нет.</p>
                    </div>
                </section>
            </aside>

            <section class="reader-panel">
                <div class="reader-toolbar">
                    <button type="button" class="bookmark" aria-label="Закладка" @click="addBookmark">+</button>
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
                            {{ translationLabel(translation) }}
                        </option>
                        <option v-if="translations.length === 0" value="L1_RST">Синодальный перевод</option>
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
                    <select
                        v-model="compareTranslationCode"
                        class="compare-select"
                        aria-label="Параллельный перевод"
                        @change="changeCompareTranslation"
                    >
                        <option value="">Один перевод</option>
                        <option
                            v-for="translation in translations.filter((item) => item.code !== selectedTranslationCode)"
                            :key="translation.code"
                            :value="translation.code"
                        >
                            + {{ translationLabel(translation) }}
                        </option>
                    </select>
                    <div class="reader-actions">
                        <button
                            type="button"
                            aria-label="Strong"
                            :class="{ active: showStrongNumbers }"
                            @click="showStrongNumbers = !showStrongNumbers"
                        >
                            S#
                        </button>
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
                        :class="{ selected: verse.id === selectedVerse?.id, highlighted: highlightedVerseNumbers.includes(verse.number) }"
                        @contextmenu="openVerseMenu($event, verse)"
                    >
                        <button
                            type="button"
                            class="verse-number"
                            @click="selectVerse(verse)"
                        >
                            {{ verse.number }}
                        </button>
                        <span class="verse-text" @click="selectVerse(verse)">
                            <template
                                v-for="part in verseTextParts(verse)"
                                :key="part.key"
                            >
                                <span>{{ part.text }}</span>
                                <button
                                    v-for="token in showStrongNumbers ? part.strongTokens : []"
                                    :key="token.id"
                                    type="button"
                                    class="strong-inline-number"
                                    @click.stop="selectInlineStrongToken(verse, token)"
                                >
                                    {{ token.strong_number }}
                                </button>
                            </template>
                        </span>
                        <small
                            v-if="compareVerseByNumber.get(verse.number)"
                            class="compare-verse"
                        >
                            <strong>{{ shortTranslationLabel(compareTranslation) }}</strong>
                            {{ compareVerseByNumber.get(verse.number)?.text }}
                        </small>
                    </p>
                    <p v-if="isCompareLoading" class="reader-status">
                        Загружаю параллельный перевод...
                    </p>
                    <p v-else-if="compareError" class="reader-status">
                        {{ compareError }}
                    </p>
                    <div
                        v-if="verseMenu"
                        class="verse-menu"
                        :style="{ left: `${verseMenu.x}px`, top: `${verseMenu.y}px` }"
                        @click.stop
                    >
                        <button type="button" @click="copyVerseReference(verseMenu.verse)">Копировать ссылку</button>
                        <button type="button" @click="copyVerseText(verseMenu.verse)">Копировать стих</button>
                        <button type="button" @click="openVerseStudy(verseMenu.verse)">Открыть справочник</button>
                    </div>
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
                    <span v-if="verseActionMessage" class="reader-toast">{{ verseActionMessage }}</span>
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
                    <button type="button" :class="{ active: activeStudyTab === 'strong' }" @click="activeStudyTab = 'strong'">Strong</button>
                    <button type="button" :class="{ active: activeStudyTab === 'references' }" @click="activeStudyTab = 'references'">Параллельные места</button>
                    <button type="button" :class="{ active: activeStudyTab === 'notes' }" @click="activeStudyTab = 'notes'">Заметки</button>
                </div>

                <section class="note">
                    <div class="note-meta">
                        <div class="avatar small">{{ selectedLanguage.slice(0, 2).toUpperCase() }}</div>
                        <strong>{{ selectedVerseReference }}</strong>
                        <span>{{ shortTranslationLabel(currentTranslation) }}</span>
                    </div>
                    <p v-if="isLoading">Загружаю языки и канон из API.</p>
                    <p v-else-if="apiError">API: {{ apiError }}</p>
                    <p v-else-if="studyError">API: {{ studyError }}</p>
                    <p v-else-if="isStudyLoading">Загружаю справочник стиха.</p>
                    <p v-else>
                        Strong: {{ strongTokens.length }}. Ссылки: {{ crossReferences.length }}.
                    </p>
                </section>

                <section v-if="activeStudyTab === 'strong'" class="events">
                    <h3>Strong</h3>
                    <div class="study-list">
                        <button
                            v-for="token in strongTokens"
                            :key="token.id"
                            type="button"
                            @click="selectStrongToken(token)"
                        >
                            <strong>{{ token.strong_number }}</strong>
                            <span>{{ token.surface_text ?? token.entry.transliteration ?? token.entry.word ?? '' }}</span>
                        </button>
                        <p v-if="!isStudyLoading && strongTokens.length === 0">Нет Strong-разметки.</p>
                    </div>
                    <article v-if="selectedStrongEntry" class="strong-entry">
                        <h3>{{ selectedStrongEntry.number }} · {{ selectedStrongEntry.word ?? selectedStrongEntry.transliteration }}</h3>
                        <p v-if="selectedStrongEntry.transliteration">Транслитерация: {{ selectedStrongEntry.transliteration }}</p>
                        <p v-if="selectedStrongEntry.pronunciation">Произношение: {{ selectedStrongEntry.pronunciation }}</p>
                        <p>{{ selectedStrongEntry.content ?? selectedStrongEntry.raw_content }}</p>
                    </article>
                </section>

                <section v-if="activeStudyTab === 'references'" class="events">
                    <h3>Параллельные места</h3>
                    <div class="reference-list">
                        <button
                            v-for="reference in visibleCrossReferences"
                            :key="reference.id"
                            type="button"
                            @click="openCrossReference(reference)"
                        >
                            <strong>{{ crossReferenceLabel(reference) }}</strong>
                            <span>{{ reference.target.text ?? reference.type }}</span>
                        </button>
                        <p v-if="!isStudyLoading && crossReferences.length === 0">Нет ссылок.</p>
                    </div>
                </section>

                <form v-if="activeStudyTab === 'notes'" class="comment-form" @submit.prevent="submitVerseNote">
                    <div class="note-list">
                        <p v-if="isNotesLoading">Загружаю заметки...</p>
                        <p v-else-if="noteError">API: {{ noteError }}</p>
                        <article
                            v-for="note in verseNotes"
                            :key="note.id"
                        >
                            {{ note.body }}
                        </article>
                        <p v-if="!isNotesLoading && !noteError && verseNotes.length === 0">Заметок к стиху пока нет.</p>
                    </div>
                    <textarea
                        v-model="noteBody"
                        placeholder="Напишите свой комментарий"
                        :disabled="!selectedVerse?.id || isNotesLoading"
                    ></textarea>
                    <button type="submit" :disabled="!selectedVerse?.id || noteBody.trim().length === 0 || isNotesLoading">Написать</button>
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
