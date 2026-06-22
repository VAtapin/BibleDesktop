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
    module: {
        code: string;
        name: string;
        short_name: string | null;
        description: string | null;
        version: string | null;
        cover_url: string | null;
    };
    has_old_testament: boolean;
    has_new_testament: boolean;
    has_apocrypha: boolean;
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
    strongNumber: string | null;
    strongToken: StrongTokenDto | null;
    isRed: boolean;
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

type StudyDataCacheItem = {
    strongTokens: StrongTokenDto[];
    crossReferences: CrossReferenceDto[];
};

type AppUser = {
    id: number;
    name: string;
    email: string;
    dashboard_url: string;
    logout_url: string;
};

type FooterPageLink = {
    title: string;
    url: string;
};

type SocialPostDto = {
    id: number;
    author: string;
    body: string;
    visibility: string;
    reference: string | null;
    created_at: string;
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

type CalendarReadingDto = {
    id: number;
    type: 'gospel' | 'apostle' | 'psalm' | string;
    title: string | null;
    passage_ref: string;
    display_ref?: string;
    date_rule_type: string;
    text?: string;
};

type CalendarEventDto = {
    id: number;
    name: string;
    legacy_type: number | null;
    date_rule_type: string;
    is_fasting: boolean;
    type: {
        code: string;
        name: string;
        typicon_symbol: string | null;
        color: string | null;
        sort_order: number;
    } | null;
};

type CalendarDayDto = {
    date: string;
    old_style_date: string;
    pascha_date: string;
    liturgical_period: string | null;
    events: CalendarEventDto[];
    fasting_events: CalendarEventDto[];
    readings: CalendarReadingDto[];
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

type HistoryItem = {
    id: string;
    title: string;
    translationCode: string;
    bookSlug: string;
    chapterNumber: number;
    verseNumber: number | null;
    openedAt: string;
};

type LeftPanelId = 'library' | 'calendar' | 'bookmarks' | 'history' | 'search';
type ToolId = LeftPanelId | 'strong' | 'print';
type IconName = 'book-open' | 'bookmark' | 'calendar' | 'clock' | 'close' | 'hash' | 'menu' | 'plus' | 'printer' | 'search' | 'sidebar';

declare global {
    interface Window {
        BibleDesktop?: {
            user: AppUser | null;
            auth: {
                login_url: string;
                register_url: string;
            };
            embed: {
                enabled: boolean;
                source: string | null;
            };
            footer_pages: FooterPageLink[];
        };
    }
}

const readerStateKey = 'bible-desktop-reader-state';
const readerFontSizeStateKey = 'bible-desktop-reader-font-size';
const bookmarkStateKey = 'bible-desktop-bookmarks';
const historyStateKey = 'bible-desktop-view-history';
const localNotesStateKey = 'bible-desktop-local-notes';
const maxReaderTabs = 8;
const appConfig = window.BibleDesktop ?? {
    user: null,
    auth: {
        login_url: '/login',
        register_url: '/register',
    },
    embed: {
        enabled: false,
        source: null,
    },
    footer_pages: [],
};
const currentUser = ref<AppUser | null>(appConfig.user);
const footerPages = ref<FooterPageLink[]>(appConfig.footer_pages ?? []);
const isEmbed = computed(() => appConfig.embed?.enabled === true);
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
const studyReturnVerseNumber = ref<number | null>(null);
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
const activeLeftPanel = ref<LeftPanelId | null>(null);
const advancedSearchQuery = ref('');
const advancedSearchScope = ref('canonical');
const advancedSearchMatch = ref<'all_words' | 'phrase' | 'partial'>('all_words');
const advancedSearchResults = ref<SearchResultDto[]>([]);
const isAdvancedSearchLoading = ref(false);
const advancedSearchError = ref<string | null>(null);
const calendarDay = ref<CalendarDayDto | null>(null);
const isCalendarLoading = ref(false);
const calendarError = ref<string | null>(null);
const expandedCalendarReadings = ref<number[]>([]);
const bookmarks = ref<BookmarkItem[]>([]);
const viewHistory = ref<HistoryItem[]>([]);
const highlightedVerseNumbers = ref<number[]>([]);
const selectionAnchorVerseNumber = ref<number | null>(null);
const readerTabs = ref<ReaderTab[]>([]);
const activeTabId = ref('');
const activeStudyTab = ref<'strong' | 'references' | 'notes' | 'feed'>('strong');
const isStudyPanelOpen = ref(false);
const socialPosts = ref<SocialPostDto[]>([]);
const socialPostBody = ref('');
const isSocialFeedLoading = ref(false);
const socialFeedError = ref<string | null>(null);
const verseMenu = ref<{ verse: Verse; x: number; y: number } | null>(null);
const verseActionMessage = ref('');
const showStrongNumbers = ref(false);
const readerFontSize = ref(defaultReaderFontSize());
const strongTooltip = ref<{ number: string; text: string; x: number; y: number } | null>(null);
let strongTooltipTimer: number | undefined;
let verseActionMessageTimer: number | undefined;

const currentVerses = ref<Verse[]>([]);
const compareVerses = ref<Verse[]>([]);
const isCompareLoading = ref(false);
const compareError = ref<string | null>(null);
const chapterCache = new Map<string, ChapterDto>();
const studyDataCache = new Map<string, StudyDataCacheItem>();

const readerStyle = computed(() => ({
    '--reader-font-size': `${readerFontSize.value}px`,
}));

const tools = [
    { id: 'library', icon: 'book-open', title: 'Библиотека' },
    { id: 'calendar', icon: 'calendar', title: 'Календарь' },
    { id: 'bookmarks', icon: 'bookmark', title: 'Закладки' },
    { id: 'history', icon: 'clock', title: 'История просмотра' },
    { id: 'search', icon: 'search', title: 'Поиск' },
    { id: 'strong', icon: 'hash', title: 'Strong' },
    { id: 'print', icon: 'printer', title: 'Печать' },
] satisfies Array<{ id: ToolId; icon: IconName; title: string }>;

function defaultReaderFontSize(): number {
    if (typeof window === 'undefined') {
        return 15;
    }

    return window.matchMedia('(max-width: 760px)').matches ? 16 : 15;
}

function changeReaderFontSize(delta: number): void {
    readerFontSize.value = Math.min(22, Math.max(13, readerFontSize.value + delta));
}

const icons: Record<IconName, string[]> = {
    'book-open': [
        'M4 19.5V5.75A2.75 2.75 0 0 1 6.75 3H20v16H7.25A3.25 3.25 0 0 0 4 22V19.5Z',
        'M4 19.5A3.5 3.5 0 0 1 7.5 16H20',
    ],
    bookmark: [
        'M6 4.75C6 3.78 6.78 3 7.75 3h8.5C17.22 3 18 3.78 18 4.75V21l-6-3.5L6 21V4.75Z',
    ],
    calendar: [
        'M7 3v4',
        'M17 3v4',
        'M4 8h16',
        'M6.75 5h10.5A2.75 2.75 0 0 1 20 7.75v9.5A2.75 2.75 0 0 1 17.25 20H6.75A2.75 2.75 0 0 1 4 17.25v-9.5A2.75 2.75 0 0 1 6.75 5Z',
        'M8 12h2',
        'M14 12h2',
        'M8 16h2',
        'M14 16h2',
    ],
    clock: [
        'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z',
        'M12 7v5l3 2',
    ],
    close: [
        'M18 6 6 18',
        'm6 6 12 12',
    ],
    hash: [
        'M5 9h14',
        'M4 15h14',
        'M10 3 8 21',
        'm16 3-2 18',
    ],
    menu: [
        'M4 6h16',
        'M4 12h16',
        'M4 18h16',
    ],
    plus: [
        'M12 5v14',
        'M5 12h14',
    ],
    printer: [
        'M7 8V4h10v4',
        'M7 18h10v-5H7v5Z',
        'M6 14H5a2 2 0 0 1-2-2v-1a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v1a2 2 0 0 1-2 2h-1',
    ],
    search: [
        'm21 21-4.35-4.35',
        'M10.5 18a7.5 7.5 0 1 0 0-15 7.5 7.5 0 0 0 0 15Z',
    ],
    sidebar: [
        'M4 5.75C4 4.78 4.78 4 5.75 4h12.5c.97 0 1.75.78 1.75 1.75v12.5c0 .97-.78 1.75-1.75 1.75H5.75C4.78 20 4 19.22 4 18.25V5.75Z',
        'M9 4v16',
        'M13 8h4',
        'M13 12h4',
        'M13 16h4',
    ],
};

function iconPaths(name: IconName): string[] {
    return icons[name];
}

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
const leftPanelTitle = computed(() => {
    if (activeLeftPanel.value === 'library') {
        return 'Библиотека';
    }

    if (activeLeftPanel.value === 'bookmarks') {
        return 'Закладки';
    }

    if (activeLeftPanel.value === 'calendar') {
        return 'Календарь дня';
    }

    if (activeLeftPanel.value === 'history') {
        return 'История просмотра';
    }

    return 'Поиск';
});
const selectedVerseReference = computed(() => {
    return selectedVerse.value ? verseReference(selectedVerse.value) : currentTitle.value;
});
const compareVerseByNumber = computed(() => {
    return new Map(compareVerses.value.map((verse) => [verse.number, verse]));
});
const selectedVerses = computed(() => {
    const selectedNumbers = [...highlightedVerseNumbers.value].sort((left, right) => left - right);

    return currentVerses.value.filter((verse) => selectedNumbers.includes(verse.number));
});
const printableVerses = computed(() => {
    if (selectedVerses.value.length > 0) {
        return selectedVerses.value;
    }

    return currentVerses.value;
});
const printTitle = computed(() => {
    if (printableVerses.value.length === 1) {
        return `${currentTitle.value}:${printableVerses.value[0]?.number}`;
    }

    if (selectedVerses.value.length > 1) {
        const lastSelectedVerse = selectedVerses.value[selectedVerses.value.length - 1];

        return `${currentTitle.value}:${selectedVerses.value[0]?.number}-${lastSelectedVerse?.number}`;
    }

    return currentTitle.value;
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

function moduleCardTitle(translation: TranslationDto): string {
    return translation.module?.name || translation.name || translationLabel(translation);
}

function moduleCardDescription(translation: TranslationDto): string {
    const description = translation.module?.description?.trim();

    if (description) {
        return description;
    }

    const parts = [
        translation.language.name,
        translation.has_strong ? 'Strong' : null,
        translation.has_apocrypha ? 'с апокрифами' : 'канон',
        translation.module?.version ? `версия ${translation.module.version}` : null,
    ].filter(Boolean);

    return parts.join(' · ');
}

function verseTextParts(verse: Verse): VerseTextPart[] {
    const text = showStrongNumbers.value ? (verse.text ?? '') : stripStrongNumbers(verse.text ?? '');
    const pattern = /(<\s*font\b[^>]*color\s*=\s*["']?darkred["']?[^>]*>|<\s*\/\s*font\s*>|\b(?:[GH]\d{1,5}|\d{1,5})\b)/giu;
    const parts: VerseTextPart[] = [];

    if (!verse.has_strong_markup && !hasRedLetterMarkup(text)) {
        return [{
            key: `${verse.id ?? verse.number}-text`,
            text: cleanDisplayChunk(text),
            strongNumber: null,
            strongToken: null,
            isRed: false,
        }];
    }

    let cursor = 0;
    let index = 0;
    let isRed = false;

    for (const match of text.matchAll(pattern)) {
        const matchIndex = match.index ?? 0;

        if (matchIndex > cursor) {
            parts.push({
                key: `${verse.id ?? verse.number}-text-${index++}`,
                text: cleanDisplayChunk(text.slice(cursor, matchIndex)),
                strongNumber: null,
                strongToken: null,
                isRed,
            });
        }

        const token = match[0];

        if (isRedLetterOpenTag(token)) {
            isRed = true;
            cursor = matchIndex + token.length;
            continue;
        }

        if (isRedLetterCloseTag(token)) {
            isRed = false;
            cursor = matchIndex + token.length;
            continue;
        }

        parts.push({
            key: `${verse.id ?? verse.number}-strong-${index++}`,
            text: '',
            strongNumber: token,
            strongToken: verse.strong_tokens?.find((candidate) => candidate.strong_number === token) ?? null,
            isRed,
        });
        cursor = matchIndex + token.length;
    }

    if (cursor < text.length) {
        parts.push({
            key: `${verse.id ?? verse.number}-text-${index}`,
            text: cleanDisplayChunk(text.slice(cursor)),
            strongNumber: null,
            strongToken: null,
            isRed,
        });
    }

    return parts;
}

function hasRedLetterMarkup(text: string): boolean {
    return /<\s*font\b[^>]*color\s*=\s*["']?darkred["']?[^>]*>/iu.test(text);
}

function isRedLetterOpenTag(text: string): boolean {
    return /^<\s*font\b[^>]*color\s*=\s*["']?darkred["']?[^>]*>$/iu.test(text);
}

function isRedLetterCloseTag(text: string): boolean {
    return /^<\s*\/\s*font\s*>$/iu.test(text);
}

function cleanDisplayChunk(text: string): string {
    return text.replace(/<[^>]+>/gu, '');
}

function displayVerseText(verse: Verse | undefined | null): string {
    if (!verse) {
        return '';
    }

    return cleanDisplayChunk(
        stripStrongNumbers(verse.text),
    ).replace(/\s+([,.;:!?])/gu, '$1').replace(/\s{2,}/gu, ' ').trim();
}

function displayReferenceText(reference: CrossReferenceDto): string {
    return cleanDisplayChunk(stripStrongNumbers(reference.target.text ?? reference.type)).trim();
}

function stripStrongNumbers(text: string): string {
    return text
        .replace(/\s*\b(?:[GH]\d{1,5}|\d{1,5})\b/giu, '')
        .replace(/\s+([,.;:!?])/gu, '$1')
        .replace(/\s{2,}/gu, ' ');
}

function strongEntrySummary(entry: StrongEntryDto): string {
    const title = [entry.word, entry.transliteration, entry.pronunciation]
        .filter((part) => typeof part === 'string' && part.trim().length > 0)
        .join(' · ');
    const body = cleanDisplayChunk(entry.content ?? entry.raw_content ?? '')
        .replace(/\s+/gu, ' ')
        .trim();

    return [title, body].filter((part) => part.length > 0).join(' — ').slice(0, 260);
}

function scrollStudyPanelIntoView(): void {
    window.requestAnimationFrame(() => {
        document.querySelector('.analysis-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}

function scrollSelectedVerseIntoView(): void {
    const verseNumber = studyReturnVerseNumber.value ?? selectedVerse.value?.number ?? null;

    if (verseNumber === null) {
        return;
    }

    document
        .querySelector(`[data-verse-number="${verseNumber}"]`)
        ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function strongTooltipPosition(event: MouseEvent | FocusEvent): { x: number; y: number } {
    const target = event.currentTarget instanceof HTMLElement ? event.currentTarget : null;
    const rect = target?.getBoundingClientRect();
    const rawX = rect ? rect.left + 10 : 12;
    const rawY = rect ? rect.bottom + 8 : 12;

    return {
        x: Math.max(12, Math.min(rawX, window.innerWidth - 292)),
        y: Math.max(12, Math.min(rawY, window.innerHeight - 150)),
    };
}

function showStrongTooltip(event: MouseEvent | FocusEvent, verse: Verse, strongNumber: string): void {
    hideStrongTooltip();

    const position = strongTooltipPosition(event);
    strongTooltipTimer = window.setTimeout(async () => {
        try {
            const verseQuery = verse.id ? `?verse=${verse.id}` : '';
            const response = await loadJson<ApiResponse<StrongEntryDto>>(`/api/strong/${strongNumber}${verseQuery}`);
            strongTooltip.value = {
                number: response.data.number,
                text: strongEntrySummary(response.data) || 'Strong: данные найдены.',
                ...position,
            };
        } catch {
            strongTooltip.value = {
                number: strongNumber,
                text: 'Strong: нет данных в словаре.',
                ...position,
            };
        }
    }, 220);
}

function hideStrongTooltip(): void {
    if (strongTooltipTimer) {
        window.clearTimeout(strongTooltipTimer);
        strongTooltipTimer = undefined;
    }

    strongTooltip.value = null;
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

function readHistory(): HistoryItem[] {
    if (typeof window === 'undefined') {
        return [];
    }

    try {
        const value = window.localStorage.getItem(historyStateKey);
        const parsed = value ? JSON.parse(value) : [];

        return Array.isArray(parsed) ? parsed.filter((item): item is HistoryItem => {
            return item
                && typeof item.id === 'string'
                && typeof item.title === 'string'
                && typeof item.translationCode === 'string'
                && typeof item.bookSlug === 'string'
                && typeof item.chapterNumber === 'number';
        }) : [];
    } catch {
        return [];
    }
}

function persistHistory(): void {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(historyStateKey, JSON.stringify(viewHistory.value));
}

function readLocalNotes(): Record<string, NoteDto[]> {
    if (typeof window === 'undefined') {
        return {};
    }

    try {
        const value = window.localStorage.getItem(localNotesStateKey);
        const parsed = value ? JSON.parse(value) : {};

        return parsed && typeof parsed === 'object' && !Array.isArray(parsed)
            ? parsed as Record<string, NoteDto[]>
            : {};
    } catch {
        return {};
    }
}

function writeLocalNotes(notes: Record<string, NoteDto[]>): void {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(localNotesStateKey, JSON.stringify(notes));
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

function readReaderFontSize(): number {
    if (typeof window === 'undefined') {
        return defaultReaderFontSize();
    }

    const stored = Number(window.localStorage.getItem(readerFontSizeStateKey));

    return Number.isFinite(stored) && stored >= 13 && stored <= 22
        ? stored
        : defaultReaderFontSize();
}

function persistReaderFontSize(): void {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(readerFontSizeStateKey, String(readerFontSize.value));
}

function toggleLeftPanel(panel: LeftPanelId): void {
    activeLeftPanel.value = activeLeftPanel.value === panel ? null : panel;
    if (activeLeftPanel.value !== null) {
        isStudyPanelOpen.value = false;
    }

    if (activeLeftPanel.value === 'calendar') {
        void loadCalendarDay();
    }
}

function handleToolClick(toolId: ToolId): void {
    if (toolId === 'library' || toolId === 'calendar' || toolId === 'bookmarks' || toolId === 'history' || toolId === 'search') {
        toggleLeftPanel(toolId);
        return;
    }

    if (toolId === 'print') {
        printPage();
        return;
    }

    if (toolId === 'strong') {
        showStrongNumbers.value = !showStrongNumbers.value;
    }
}

async function loadCalendarDay(): Promise<void> {
    if (calendarDay.value || isCalendarLoading.value) {
        return;
    }

    isCalendarLoading.value = true;
    calendarError.value = null;

    try {
        const params = new URLSearchParams({ translation: selectedTranslationCode.value });
        const response = await loadJson<ApiResponse<CalendarDayDto>>(`/api/calendar/day?${params.toString()}`);
        calendarDay.value = response.data;
    } catch (error) {
        calendarError.value = error instanceof Error ? error.message : 'Не удалось загрузить календарь дня';
    } finally {
        isCalendarLoading.value = false;
    }
}

function toggleCalendarReading(readingId: number): void {
    expandedCalendarReadings.value = expandedCalendarReadings.value.includes(readingId)
        ? expandedCalendarReadings.value.filter((id) => id !== readingId)
        : [...expandedCalendarReadings.value, readingId];
}

function isCalendarReadingExpanded(readingId: number): boolean {
    return expandedCalendarReadings.value.includes(readingId);
}

function calendarReadingTypeLabel(type: string): string {
    return matchCalendarReadingType(type);
}

function matchCalendarReadingType(type: string): string {
    if (type === 'gospel') {
        return 'Евангелие';
    }

    if (type === 'apostle') {
        return 'Апостол';
    }

    if (type === 'psalm') {
        return 'Псалтирь';
    }

    return type;
}

function printPage(): void {
    window.print();
}

async function loadJson<T>(url: string): Promise<T> {
    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
        },
    });

    if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
    }

    return response.json() as Promise<T>;
}

function chapterCacheKey(translationCode: string, bookSlug: string, chapterNumber: number): string {
    return `${translationCode}:${bookSlug}:${chapterNumber}`;
}

function applyChapterData(chapter: ChapterDto, targetVerseNumber: number | null): void {
    currentVerses.value = chapter.verses;

    const preferredVerseNumber = targetVerseNumber ?? highlightedVerseNumbers.value[0] ?? null;
    selectedVerse.value = preferredVerseNumber === null
        ? currentVerses.value[0] ?? null
        : currentVerses.value.find((verse) => verse.number === preferredVerseNumber) ?? currentVerses.value[0] ?? null;
}

function resetVerseSelection(): void {
    highlightedVerseNumbers.value = [];
    selectionAnchorVerseNumber.value = null;
    selectedVerse.value = null;
    studyReturnVerseNumber.value = null;
}

async function postJson<T>(url: string, payload: Record<string, unknown>): Promise<T> {
    const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
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

    const cacheKey = chapterCacheKey(selectedTranslationCode.value, selectedBookSlug.value, selectedChapterNumber.value);
    const cachedChapter = chapterCache.get(cacheKey);
    isChapterLoading.value = cachedChapter === undefined;

    try {
        const chapter = cachedChapter ?? (await loadJson<ApiResponse<ChapterDto>>(
            `/api/translations/${selectedTranslationCode.value}/books/${selectedBookSlug.value}/chapters/${selectedChapterNumber.value}`,
        )).data;

        if (!cachedChapter) {
            chapterCache.set(cacheKey, chapter);
        }

        applyChapterData(chapter, targetVerseNumber);
        if (selectedVerse.value?.id) {
            await loadStudyData(selectedVerse.value);
        }
        await loadCompareChapter();
        recordHistory(selectedVerse.value?.number ?? null);
        apiError.value = null;
    } catch (error) {
        currentVerses.value = [];
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

    const cacheKey = chapterCacheKey(compareTranslationCode.value, selectedBookSlug.value, selectedChapterNumber.value);
    const cachedChapter = chapterCache.get(cacheKey);
    isCompareLoading.value = cachedChapter === undefined;

    try {
        const chapter = cachedChapter ?? (await loadJson<ApiResponse<ChapterDto>>(
            `/api/translations/${compareTranslationCode.value}/books/${selectedBookSlug.value}/chapters/${selectedChapterNumber.value}`,
        )).data;

        if (!cachedChapter) {
            chapterCache.set(cacheKey, chapter);
        }

        compareVerses.value = chapter.verses;
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
    resetVerseSelection();
    await loadBooksForSelectedTranslation();
    await loadChapter();
    syncActiveTabFromSelection();
    persistReaderState();
}

function changeBook(): void {
    selectedChapterNumber.value = 1;
    resetVerseSelection();
    void loadChapter();
}

function changeCompareTranslation(): void {
    void loadCompareChapter();
    persistReaderState();
}

function changeChapter(): void {
    resetVerseSelection();
    void loadChapter();
}

function goChapter(delta: number): void {
    const nextChapter = selectedChapterNumber.value + delta;
    const maxChapter = currentBook.value?.chapters_count ?? 1;

    if (nextChapter < 1 || nextChapter > maxChapter) {
        return;
    }

    selectedChapterNumber.value = nextChapter;
    resetVerseSelection();
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
        text: displayVerseText(verse),
        createdAt: new Date().toISOString(),
    };

    bookmarks.value = [
        bookmark,
        ...bookmarks.value.filter((item) => item.id !== bookmark.id),
    ].slice(0, 100);
    persistBookmarks();
    activeLeftPanel.value = 'bookmarks';
    if (currentUser.value) {
        void postJson('/reader/bookmarks', {
            verse_id: verse.id,
            title: bookmark.reference,
            description: bookmark.text,
        }).catch(() => {
            showVerseActionMessage('Закладка сохранена локально, но не синхронизирована');
        });
    }
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

function recordHistory(verseNumber: number | null = null): void {
    const versePart = verseNumber ? `:${verseNumber}` : '';
    const item: HistoryItem = {
        id: `${selectedTranslationCode.value}:${selectedBookSlug.value}:${selectedChapterNumber.value}${versePart}`,
        title: `${currentTitle.value}${versePart}`,
        translationCode: selectedTranslationCode.value,
        bookSlug: selectedBookSlug.value,
        chapterNumber: selectedChapterNumber.value,
        verseNumber,
        openedAt: new Date().toISOString(),
    };

    viewHistory.value = [
        item,
        ...viewHistory.value.filter((historyItem) => historyItem.id !== item.id),
    ].slice(0, 100);
    persistHistory();
}

async function openHistoryItem(item: HistoryItem): Promise<void> {
    selectedTranslationCode.value = item.translationCode;
    selectedBookSlug.value = item.bookSlug;
    selectedChapterNumber.value = item.chapterNumber;
    highlightedVerseNumbers.value = item.verseNumber ? [item.verseNumber] : [];

    await loadBooksForSelectedTranslation();
    await loadChapter(item.verseNumber);
    syncActiveTabFromSelection();
    persistReaderState();
}

function clearHistory(): void {
    viewHistory.value = [];
    persistHistory();
}

async function openLibraryTranslation(translation: TranslationDto): Promise<void> {
    if (translation.code === selectedTranslationCode.value) {
        activeLeftPanel.value = null;
        return;
    }

    selectedTranslationCode.value = translation.code;
    selectedChapterNumber.value = 1;
    highlightedVerseNumbers.value = [];

    await loadBooksForSelectedTranslation();
    selectedBookSlug.value = books.value.find((book) => book.slug === selectedBookSlug.value)?.slug
        ?? books.value[0]?.slug
        ?? 'genesis';
    await loadChapter();
    syncActiveTabFromSelection();
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
        strongTokens.value = [];
        selectedStrongEntry.value = null;
        crossReferences.value = [];
        verseNotes.value = [];
        return;
    }

    const cacheKey = `${selectedTranslationCode.value}:${verse.id}`;
    const cachedStudyData = studyDataCache.get(cacheKey);

    if (cachedStudyData) {
        strongTokens.value = cachedStudyData.strongTokens;
        crossReferences.value = cachedStudyData.crossReferences;
        await loadVerseNotes(verse.id);
        return;
    }

    isStudyLoading.value = true;
    studyError.value = null;

    try {
        const [strongResponse, referencesResponse] = await Promise.all([
            loadJson<ApiResponse<{ tokens: StrongTokenDto[] }>>(
                `/api/verses/${verse.id}/strong-tokens?translation=${selectedTranslationCode.value}`,
            ),
            loadJson<ApiResponse<{ references: CrossReferenceDto[] }>>(
                `/api/verses/${verse.id}/cross-references?translation=${selectedTranslationCode.value}`,
            ),
        ]);

        strongTokens.value = verse.strong_tokens?.length ? verse.strong_tokens : strongResponse.data.tokens;
        selectedStrongEntry.value = null;
        crossReferences.value = referencesResponse.data.references;
        studyDataCache.set(cacheKey, {
            strongTokens: strongTokens.value,
            crossReferences: crossReferences.value,
        });
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

async function selectStrongNumber(strongNumber: string, verse: Verse | null = selectedVerse.value): Promise<void> {
    studyReturnVerseNumber.value = verse?.number ?? selectedVerse.value?.number ?? null;
    activeStudyTab.value = 'strong';
    isStudyPanelOpen.value = true;
    activeLeftPanel.value = null;
    studyError.value = null;
    scrollStudyPanelIntoView();

    try {
        const verseQuery = verse?.id ? `?verse=${verse.id}` : '';
        const response = await loadJson<ApiResponse<StrongEntryDto>>(`/api/strong/${strongNumber}${verseQuery}`);
        selectedStrongEntry.value = response.data;
        scrollStudyPanelIntoView();
    } catch (error) {
        selectedStrongEntry.value = null;
        studyError.value = error instanceof Error ? error.message : 'Не удалось загрузить номер Strong';
        scrollStudyPanelIntoView();
    }
}

async function selectStrongToken(token: StrongTokenDto): Promise<void> {
    await selectStrongNumber(token.strong_number);
}

async function selectInlineStrongToken(verse: Verse, token: StrongTokenDto): Promise<void> {
    await selectInlineStrongNumber(verse, token.strong_number, token);
}

async function selectInlineStrongNumber(verse: Verse, strongNumber: string, token: StrongTokenDto | null = null): Promise<void> {
    const previousVerseId = selectedVerse.value?.id;

    selectedVerse.value = verse;
    highlightedVerseNumbers.value = [verse.number];
    studyReturnVerseNumber.value = verse.number;
    activeStudyTab.value = 'strong';

    if (verse.id && verse.id !== previousVerseId) {
        await loadStudyData(verse);
    }

    await selectStrongNumber(token?.strong_number ?? strongNumber, verse);
}

async function loadVerseNotes(verseId: number): Promise<void> {
    isNotesLoading.value = true;
    noteError.value = null;

    try {
        if (!currentUser.value) {
            verseNotes.value = readLocalNotes()[String(verseId)] ?? [];
            return;
        }

        const response = await loadJson<ApiResponse<{ notes: NoteDto[] }>>(`/reader/verses/${verseId}/notes`);
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
        if (!currentUser.value) {
            const notes = readLocalNotes();
            const now = new Date().toISOString();
            const verseId = String(selectedVerse.value.id);
            notes[verseId] = [
                {
                    id: Date.now(),
                    body,
                    visibility: 'local',
                    created_at: now,
                    updated_at: now,
                },
                ...(notes[verseId] ?? []),
            ].slice(0, 100);
            writeLocalNotes(notes);
        } else {
            await postJson<ApiResponse<{ note: NoteDto }>>(`/reader/verses/${selectedVerse.value.id}/notes`, { body });
        }

        noteBody.value = '';
        await loadVerseNotes(selectedVerse.value.id);
    } catch (error) {
        noteError.value = error instanceof Error ? error.message : 'Не удалось сохранить заметку';
    } finally {
        isNotesLoading.value = false;
    }
}

async function loadSocialFeed(): Promise<void> {
    if (!currentUser.value) {
        socialPosts.value = [];
        return;
    }

    isSocialFeedLoading.value = true;
    socialFeedError.value = null;

    try {
        const response = await loadJson<ApiResponse<{ posts: SocialPostDto[] }>>('/reader/feed');
        socialPosts.value = response.data.posts;
    } catch (error) {
        socialPosts.value = [];
        socialFeedError.value = error instanceof Error ? error.message : 'Не удалось загрузить ленту';
    } finally {
        isSocialFeedLoading.value = false;
    }
}

async function submitSocialPost(): Promise<void> {
    const body = socialPostBody.value.trim();

    if (!currentUser.value || body.length === 0) {
        return;
    }

    isSocialFeedLoading.value = true;
    socialFeedError.value = null;

    try {
        await postJson('/reader/feed', {
            body,
            verse_id: selectedVerse.value?.id ?? null,
            visibility: 'followers',
        });
        socialPostBody.value = '';
        await loadSocialFeed();
    } catch (error) {
        socialFeedError.value = error instanceof Error ? error.message : 'Не удалось опубликовать';
    } finally {
        isSocialFeedLoading.value = false;
    }
}

function selectVerse(verse: Verse, event: MouseEvent | null = null): void {
    selectedVerse.value = verse;
    const currentSelection = [...highlightedVerseNumbers.value];

    if (event?.shiftKey && selectionAnchorVerseNumber.value !== null) {
        const start = Math.min(selectionAnchorVerseNumber.value, verse.number);
        const end = Math.max(selectionAnchorVerseNumber.value, verse.number);
        highlightedVerseNumbers.value = currentVerses.value
            .map((candidate) => candidate.number)
            .filter((number) => number >= start && number <= end);
    } else if (event?.ctrlKey || event?.metaKey) {
        highlightedVerseNumbers.value = currentSelection.includes(verse.number)
            ? currentSelection.filter((number) => number !== verse.number)
            : [...currentSelection, verse.number].sort((left, right) => left - right);

        if (highlightedVerseNumbers.value.length === 0) {
            highlightedVerseNumbers.value = [verse.number];
        }

        selectionAnchorVerseNumber.value = verse.number;
    } else {
        highlightedVerseNumbers.value = [verse.number];
        selectionAnchorVerseNumber.value = verse.number;
    }

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
    const selectedNumbers = selectedVerses.value.length > 0
        ? selectedVerses.value.map((selected) => selected.number)
        : [verse.number];

    url.search = new URLSearchParams({
        translation: selectedTranslationCode.value,
        book: selectedBookSlug.value,
        chapter: String(selectedChapterNumber.value),
        verses: selectedNumbers.join(','),
    }).toString();

    return url.toString();
}

function openVerseMenu(event: MouseEvent, verse: Verse): void {
    event.preventDefault();
    selectedVerse.value = verse;
    if (!highlightedVerseNumbers.value.includes(verse.number)) {
        highlightedVerseNumbers.value = [verse.number];
        selectionAnchorVerseNumber.value = verse.number;
    }
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
    const verses = selectedVerses.value.length > 0 ? selectedVerses.value : [verse];
    const text = verses
        .map((selected) => `${verseReference(selected)} ${displayVerseText(selected)}`)
        .join('\n');

    await copyToClipboard(text);
    showVerseActionMessage(verses.length > 1 ? 'Стихи скопированы' : 'Стих скопирован');
    closeVerseMenu();
}

function openVerseStudy(verse: Verse): void {
    selectVerse(verse);
    activeStudyTab.value = 'strong';
    showVerseActionMessage('Справочник открыт');
}

function toggleVerseInSelection(verse: Verse): void {
    const selected = highlightedVerseNumbers.value.includes(verse.number);
    highlightedVerseNumbers.value = selected
        ? highlightedVerseNumbers.value.filter((number) => number !== verse.number)
        : [...highlightedVerseNumbers.value, verse.number].sort((left, right) => left - right);

    if (highlightedVerseNumbers.value.length === 0) {
        highlightedVerseNumbers.value = [verse.number];
    }

    selectedVerse.value = verse;
    selectionAnchorVerseNumber.value = verse.number;
    void loadStudyData(verse);
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
    isStudyPanelOpen.value = false;
    activeLeftPanel.value = null;

    await loadBooksForSelectedTranslation();
    await loadChapter(reference.target.verse_number);
    syncActiveTabFromSelection();
    persistReaderState();
}

onMounted(async () => {
    try {
        readerFontSize.value = readReaderFontSize();
        const savedState = readReaderState();
        const urlState = readUrlState();
        bookmarks.value = readBookmarks();
        viewHistory.value = readHistory();
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
    calendarDay.value = null;
    expandedCalendarReadings.value = [];
    syncActiveTabFromSelection();
    persistReaderState();
});

watch(readerFontSize, () => {
    persistReaderFontSize();
});

watch(activeStudyTab, (tab) => {
    if (tab === 'feed') {
        void loadSocialFeed();
    }
});
</script>

<template>
    <div class="app-shell" :class="{ 'embed-shell': isEmbed }" :style="readerStyle">
        <header class="topbar">
            <a class="brand" href="/" aria-label="Bible Desktop - на главную">
                <img class="brand-mark" :src="'/brand/bible-desktop-mark.png'" alt="" />
                <div>
                    <strong>Bible</strong>
                    <span>desktop</span>
                </div>
            </a>

            <form class="search" role="search" @submit.prevent="runSearch">
                <button type="submit" aria-label="Найти">
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <path
                            v-for="path in iconPaths('search')"
                            :key="path"
                            :d="path"
                        />
                    </svg>
                </button>
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
                            <span>Фильтры, точная фраза и часть слова</span>
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
                <a v-if="currentUser" class="profile-link" :href="currentUser.dashboard_url">
                    <div class="profile-text">
                        <strong>{{ currentUser.name }}</strong>
                        <span>Личный кабинет</span>
                    </div>
                    <div class="avatar">{{ currentUser.name.slice(0, 1).toUpperCase() }}</div>
                </a>
                <a v-else class="profile-link" :href="appConfig.auth.login_url">
                    <div class="profile-text">
                        <strong>Войти</strong>
                        <span>Личный кабинет</span>
                    </div>
                    <div class="avatar">В</div>
                </a>
            </div>
        </header>

        <section class="workspace-title">
            <span class="muted-icon">Чтение</span>
            <strong>{{ currentTitle }}</strong>
            <button type="button" aria-label="Меню">
                <svg aria-hidden="true" viewBox="0 0 24 24">
                    <path
                        v-for="path in iconPaths('menu')"
                        :key="path"
                        :d="path"
                    />
                </svg>
            </button>
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
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <path
                            v-for="path in iconPaths('close')"
                            :key="path"
                            :d="path"
                        />
                    </svg>
                </button>
            </div>
            <button
                type="button"
                class="reader-tab-add"
                :disabled="readerTabs.length >= maxReaderTabs"
                aria-label="Открыть новую вкладку"
                @click="addReaderTab"
            >
                <svg aria-hidden="true" viewBox="0 0 24 24">
                    <path
                        v-for="path in iconPaths('plus')"
                        :key="path"
                        :d="path"
                    />
                </svg>
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
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <path
                            v-for="path in iconPaths(tool.icon)"
                            :key="path"
                            :d="path"
                        />
                    </svg>
                    <span class="sr-only">{{ tool.title }}</span>
                </button>
            </aside>

            <aside v-if="activeLeftPanel" class="left-panel">
                <header>
                    <h2>{{ leftPanelTitle }}</h2>
                    <button type="button" aria-label="Закрыть" @click="activeLeftPanel = null">
                        <svg aria-hidden="true" viewBox="0 0 24 24">
                            <path
                                v-for="path in iconPaths('close')"
                                :key="path"
                                :d="path"
                            />
                        </svg>
                    </button>
                </header>

                <form v-if="activeLeftPanel === 'search'" class="advanced-search-panel" @submit.prevent="runAdvancedSearch">
                    <input v-model="advancedSearchQuery" type="search" placeholder="Введите слово или фразу" />
                    <select v-model="advancedSearchMatch">
                        <option value="all_words">Все слова</option>
                        <option value="phrase">Точная фраза</option>
                        <option value="partial">Часть слова</option>
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

                <section v-else-if="activeLeftPanel === 'library'" class="library-panel">
                    <div class="module-card-list">
                        <button
                            v-for="translation in translations"
                            :key="translation.code"
                            type="button"
                            :class="{ active: translation.code === selectedTranslationCode }"
                            @click="openLibraryTranslation(translation)"
                        >
                            <img
                                v-if="translation.module?.cover_url"
                                :src="translation.module.cover_url"
                                alt=""
                            />
                            <span v-else class="module-cover-fallback">{{ shortTranslationLabel(translation) }}</span>
                            <span class="module-card-body">
                                <strong>{{ moduleCardTitle(translation) }}</strong>
                                <small>{{ translation.language.name }} · {{ translation.has_strong ? 'Strong' : 'без Strong' }}</small>
                                <span>{{ moduleCardDescription(translation) }}</span>
                            </span>
                        </button>
                        <p v-if="translations.length === 0">Библии пока не загружены.</p>
                    </div>
                </section>

                <section v-else-if="activeLeftPanel === 'calendar'" class="calendar-panel">
                    <p v-if="isCalendarLoading">Загружаю календарь...</p>
                    <p v-else-if="calendarError">{{ calendarError }}</p>
                    <template v-else-if="calendarDay">
                        <div class="calendar-date">
                            <strong>{{ calendarDay.date }}</strong>
                            <span>{{ calendarDay.old_style_date }} ст.ст.</span>
                            <small v-if="calendarDay.liturgical_period">{{ calendarDay.liturgical_period }}</small>
                        </div>
                        <h3>События</h3>
                        <ul v-if="calendarDay.events.length > 0">
                            <li v-for="event in calendarDay.events" :key="event.id">
                                <span
                                    v-if="event.type?.typicon_symbol"
                                    class="calendar-event-symbol"
                                    :class="{ 'calendar-event-symbol-red': event.type.color === 'red' }"
                                    :title="event.type.name"
                                >
                                    {{ event.type.typicon_symbol }}
                                </span>
                                {{ event.name }}
                            </li>
                        </ul>
                        <p v-else>На этот день нет включённых событий календаря.</p>
                        <h3>Евангелие и Апостол</h3>
                        <div v-if="calendarDay.readings.length > 0" class="calendar-readings">
                            <article v-for="reading in calendarDay.readings" :key="reading.id">
                                <span>{{ calendarReadingTypeLabel(reading.type) }}</span>
                                <strong>{{ reading.title || reading.display_ref || reading.passage_ref }}</strong>
                                <button type="button" class="calendar-reading-link" @click="toggleCalendarReading(reading.id)">
                                    {{ reading.display_ref || reading.passage_ref }}
                                </button>
                                <template v-if="isCalendarReadingExpanded(reading.id)">
                                    <pre v-if="reading.text" class="calendar-reading-text">{{ reading.text }}</pre>
                                    <p v-else>Текст для выбранного перевода не найден.</p>
                                </template>
                            </article>
                        </div>
                        <p v-else>Чтения дня ещё не заданы.</p>
                    </template>
                </section>

                <section v-else-if="activeLeftPanel === 'bookmarks'" class="bookmark-panel">
                    <div v-if="!currentUser" class="guest-warning">
                        Закладки сохраняются в этом браузере и могут быть потеряны.
                        <a :href="appConfig.auth.login_url">Войти</a>
                        <a :href="appConfig.auth.register_url">Регистрация</a>
                    </div>
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

                <section v-else class="history-panel">
                    <div v-if="!currentUser" class="guest-warning">
                        История просмотра хранится только в этом браузере.
                        <a :href="appConfig.auth.login_url">Войти</a>
                        <a :href="appConfig.auth.register_url">Регистрация</a>
                    </div>
                    <div class="history-list">
                        <button
                            v-for="item in viewHistory"
                            :key="item.id"
                            type="button"
                            @click="openHistoryItem(item)"
                        >
                            <strong>{{ item.title }}</strong>
                            <span>{{ item.translationCode }}</span>
                        </button>
                        <p v-if="viewHistory.length === 0">История пока пуста.</p>
                    </div>
                    <button type="button" class="history-clear" :disabled="viewHistory.length === 0" @click="clearHistory">
                        Очистить историю
                    </button>
                </section>
            </aside>

            <section class="reader-panel">
                <div class="reader-toolbar">
                    <button type="button" class="bookmark" aria-label="Добавить закладку" @click="addBookmark">
                        <svg aria-hidden="true" viewBox="0 0 24 24">
                            <path
                                v-for="path in iconPaths('bookmark')"
                                :key="path"
                                :d="path"
                            />
                        </svg>
                    </button>
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
                        @change="changeChapter"
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
                            class="reader-font-button"
                            aria-label="Уменьшить шрифт"
                            title="Уменьшить шрифт"
                            @click="changeReaderFontSize(-1)"
                        >
                            A-
                        </button>
                        <button
                            type="button"
                            class="reader-font-button"
                            aria-label="Увеличить шрифт"
                            title="Увеличить шрифт"
                            @click="changeReaderFontSize(1)"
                        >
                            A+
                        </button>
                        <button
                            type="button"
                            aria-label="Strong"
                            :class="{ active: showStrongNumbers }"
                            @click="showStrongNumbers = !showStrongNumbers"
                        >
                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                <path
                                    v-for="path in iconPaths('hash')"
                                    :key="path"
                                    :d="path"
                                />
                            </svg>
                        </button>
                        <button type="button" aria-label="Печать" @click="printPage">
                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                <path
                                    v-for="path in iconPaths('printer')"
                                    :key="path"
                                    :d="path"
                                />
                            </svg>
                        </button>
                        <button type="button" aria-label="Закрыть справочник" @click="isStudyPanelOpen = false">
                            <svg aria-hidden="true" viewBox="0 0 24 24">
                                <path
                                    v-for="path in iconPaths('close')"
                                    :key="path"
                                    :d="path"
                                />
                            </svg>
                        </button>
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
                        :data-verse-number="verse.number"
                        :class="{ selected: verse.id === selectedVerse?.id, highlighted: highlightedVerseNumbers.includes(verse.number) }"
                        @contextmenu="openVerseMenu($event, verse)"
                    >
                        <button
                            type="button"
                            class="verse-number"
                            @click="selectVerse(verse, $event)"
                        >
                            {{ verse.number }}
                        </button>
                        <span class="verse-text" @click="selectVerse(verse, $event)">
                            <template
                                v-for="part in verseTextParts(verse)"
                                :key="part.key"
                            >
                                <span :class="{ 'words-of-jesus': part.isRed }">{{ part.text }}</span>
                                <button
                                    v-if="showStrongNumbers && part.strongNumber"
                                    type="button"
                                    class="strong-inline-number"
                                    :class="{ 'words-of-jesus': part.isRed }"
                                    @click.stop="selectInlineStrongNumber(verse, part.strongNumber, part.strongToken)"
                                    @mouseenter="showStrongTooltip($event, verse, part.strongNumber)"
                                    @mouseleave="hideStrongTooltip"
                                    @focus="showStrongTooltip($event, verse, part.strongNumber)"
                                    @blur="hideStrongTooltip"
                                >
                                    {{ part.strongNumber }}
                                </button>
                            </template>
                        </span>
                        <small
                            v-if="compareVerseByNumber.get(verse.number)"
                            class="compare-verse"
                        >
                            <strong>{{ shortTranslationLabel(compareTranslation) }}</strong>
                            {{ displayVerseText(compareVerseByNumber.get(verse.number)) }}
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
                        <button type="button" @click="toggleVerseInSelection(verseMenu.verse)">
                            {{ highlightedVerseNumbers.includes(verseMenu.verse.number) && highlightedVerseNumbers.length > 1 ? 'Убрать из выделения' : 'Добавить к выделению' }}
                        </button>
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

            <aside class="analysis-panel" :class="{ 'is-open': isStudyPanelOpen }">
                <header>
                    <h2>Справочник</h2>
                    <button type="button" class="analysis-close" aria-label="Закрыть справочник" @click="isStudyPanelOpen = false">
                        <svg aria-hidden="true" viewBox="0 0 24 24">
                            <path
                                v-for="path in iconPaths('close')"
                                :key="path"
                                :d="path"
                            />
                        </svg>
                    </button>
                </header>

                <div class="analysis-tabs">
                    <button type="button" :class="{ active: activeStudyTab === 'strong' }" @click="activeStudyTab = 'strong'">Strong</button>
                    <button type="button" :class="{ active: activeStudyTab === 'references' }" @click="activeStudyTab = 'references'">Параллельные места</button>
                    <button type="button" :class="{ active: activeStudyTab === 'notes' }" @click="activeStudyTab = 'notes'">Заметки</button>
                    <button type="button" :class="{ active: activeStudyTab === 'feed' }" @click="activeStudyTab = 'feed'">Лента</button>
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
                    <article v-if="selectedStrongEntry" class="strong-entry">
                        <h3>{{ selectedStrongEntry.number }} · {{ selectedStrongEntry.word ?? selectedStrongEntry.transliteration }}</h3>
                        <p v-if="selectedStrongEntry.transliteration">Транслитерация: {{ selectedStrongEntry.transliteration }}</p>
                        <p v-if="selectedStrongEntry.pronunciation">Произношение: {{ selectedStrongEntry.pronunciation }}</p>
                        <div class="strong-entry-content" v-html="selectedStrongEntry.content ?? selectedStrongEntry.raw_content"></div>
                    </article>
                    <p v-else-if="!isStudyLoading && strongTokens.length > 0">Включите S# слева от текста и нажмите номер Strong в стихе.</p>
                    <p v-else-if="!isStudyLoading">Нет Strong-разметки.</p>
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
                            <span>{{ displayReferenceText(reference) }}</span>
                        </button>
                        <p v-if="!isStudyLoading && crossReferences.length === 0">Нет ссылок.</p>
                    </div>
                </section>

                <form v-if="activeStudyTab === 'notes'" class="comment-form" @submit.prevent="submitVerseNote">
                    <div v-if="!currentUser" class="guest-warning">
                        Заметки сохраняются только в этом браузере. Для постоянного хранения войдите или зарегистрируйтесь.
                        <a :href="appConfig.auth.login_url">Войти</a>
                        <a :href="appConfig.auth.register_url">Регистрация</a>
                    </div>
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

                <section v-if="activeStudyTab === 'feed'" class="social-feed">
                    <div v-if="!currentUser" class="guest-warning">
                        Лента доступна после входа.
                        <a :href="appConfig.auth.login_url">Войти</a>
                        <a :href="appConfig.auth.register_url">Регистрация</a>
                    </div>
                    <form v-else @submit.prevent="submitSocialPost">
                        <textarea v-model="socialPostBody" placeholder="Напишите публикацию"></textarea>
                        <button type="submit" :disabled="socialPostBody.trim().length === 0 || isSocialFeedLoading">
                            Опубликовать
                        </button>
                    </form>
                    <p v-if="isSocialFeedLoading">Загружаю ленту...</p>
                    <p v-else-if="socialFeedError">API: {{ socialFeedError }}</p>
                    <article
                        v-for="post in socialPosts"
                        :key="post.id"
                    >
                        <strong>{{ post.author }}</strong>
                        <small v-if="post.reference">{{ post.reference }}</small>
                        <p>{{ post.body }}</p>
                    </article>
                    <p v-if="currentUser && !isSocialFeedLoading && socialPosts.length === 0">В ленте пока нет публикаций.</p>
                </section>

                <button
                    v-if="studyReturnVerseNumber !== null"
                    type="button"
                    class="return-to-verse"
                    @click="scrollSelectedVerseIntoView"
                >
                    К стиху {{ studyReturnVerseNumber }}
                </button>
            </aside>
        </main>

        <div
            v-if="strongTooltip"
            class="strong-tooltip"
            :style="{ left: `${strongTooltip.x}px`, top: `${strongTooltip.y}px` }"
        >
            <strong>{{ strongTooltip.number }}</strong>
            <span>{{ strongTooltip.text }}</span>
        </div>

        <footer class="footerbar">
            <button type="button">{{ selectedLanguage }}</button>
            <nav>
                <a
                    v-for="page in footerPages"
                    :key="page.url"
                    :href="page.url"
                >
                    {{ page.title }}
                </a>
                <template v-if="footerPages.length === 0">
                    <a href="/pages/information">Информация</a>
                    <a href="/pages/about">О проекте</a>
                    <a href="/pages/impressum">Impressum</a>
                    <a href="/pages/contacts">Контакты</a>
                </template>
            </nav>
        </footer>
    </div>
    <section class="print-document" aria-hidden="true">
        <h1>{{ printTitle }}</h1>
        <p
            v-for="verse in printableVerses"
            :key="`print-${verse.id ?? verse.number}`"
        >
            <strong>{{ verse.number }}</strong>
            <span>{{ displayVerseText(verse) }}</span>
        </p>
        <footer>https://bible-desktop.com</footer>
    </section>
</template>
