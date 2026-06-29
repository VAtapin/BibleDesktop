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
    verse_id: number | null;
    reference: string | null;
    book_slug: string | null;
    book_name: string | null;
    book_short_name: string | null;
    chapter_number: number | null;
    verse_number: number | null;
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
    metadata: {
        fasting_rule?: string;
        meal_note?: string;
        source?: string;
    };
    type: {
        code: string;
        name: string;
        typicon_symbol: string | null;
        color: string | null;
        sort_order: number;
    } | null;
};

type MonasteryServiceDto = {
    id: number;
    title: string;
    description: string | null;
    location: string | null;
    starts_at: string;
    ends_at: string | null;
    time_label: string;
    is_all_day: boolean;
};

type CalendarDayDto = {
    date: string;
    old_style_date: string;
    pascha_date: string;
    liturgical_period: string | null;
    events: CalendarEventDto[];
    fasting_events: CalendarEventDto[];
    readings: CalendarReadingDto[];
    monastery_services: MonasteryServiceDto[];
};

type PrayerDto = {
    id: number;
    language_code: string;
    category: string;
    liturgy_key: string | null;
    title: string;
    short_title: string | null;
    intro?: string | null;
    excerpt?: string;
    body?: string;
    source_url?: string | null;
    sections?: PrayerSectionDto[];
};

type PrayerSectionDto = {
    id: number;
    title: string | null;
    body?: string;
    sort_order: number;
};

type RecipeCategoryDto = {
    id: number;
    slug: string;
    name: string;
    description: string | null;
};

type UsefulLinkDto = {
    id: number;
    slug: string;
    title: string;
    description: string | null;
    url: string;
    category: string;
    icon: string | null;
    cover_image_url: string | null;
};

type FaithQuestionDto = {
    id: number;
    slug: string;
    category: string;
    question: string;
    answer_html: string;
    source_url: string | null;
};

type RecipeDto = {
    id: number;
    title: string;
    summary: string | null;
    servings?: number;
    ingredients?: string | null;
    ingredient_items?: Array<{
        name: string;
        amount: number | null;
        unit: string | null;
        note: string | null;
    }>;
    cover_image_url: string | null;
    youtube_url?: string | null;
    fasting_rule: string | null;
    category: {
        slug: string;
        name: string;
    };
    steps?: Array<{
        step_number: number;
        body: string;
        image_url: string | null;
    }>;
};

type QuizDto = {
    id: number;
    slug: string;
    title: string;
    description: string | null;
    questions?: QuizQuestionDto[];
};

type QuizQuestionDto = {
    id: number;
    question: string;
    answer_type: string;
    image_url: string | null;
    explanation: string | null;
    recommendation: QuizRecommendationDto | null;
    answers: QuizAnswerDto[];
};

type QuizAnswerDto = {
    id: number;
    answer: string;
    is_correct: boolean;
    recommendation: QuizRecommendationDto | null;
};

type QuizRecommendationDto = {
    type: string;
    prayer_id: number | null;
    passage_ref: string | null;
    text: string | null;
};

type VirtualTourDto = {
    id: number;
    slug: string;
    title: string;
    description: string | null;
    cover_image_url: string | null;
    tour_url: string;
};

type PrintBlock = {
    title: string;
    body?: string;
    html?: string;
    image?: string | null;
    kind?: 'verse' | 'section';
};

type PrintDocument = {
    title: string;
    subtitle: string | null;
    blocks: PrintBlock[];
};

type ReaderTab = {
    id: string;
    title: string;
    translationCode: string;
    bookSlug: string;
    chapterNumber: number;
    contentMode: MainContentMode;
    contentId: number | null;
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

type LeftPanelId = 'library' | 'calendar' | 'bookmarks' | 'history' | 'search' | 'prayers' | 'faith' | 'materials' | 'recipes' | 'quizzes' | 'tours';
type StudyToolId = 'references' | 'notes' | 'feed';
type ToolId = LeftPanelId | StudyToolId | 'strong' | 'print';
type MainContentMode = 'chapter' | 'prayer' | 'faith-question' | 'recipe' | 'quiz' | 'tour';
type IconName = 'book-open' | 'bookmark' | 'calendar' | 'clock' | 'close' | 'feed' | 'globe' | 'hash' | 'link' | 'menu' | 'note' | 'plus' | 'prayer' | 'printer' | 'recipe' | 'search' | 'sidebar' | 'test';
type ReaderTheme = 'light' | 'dark';

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
const readerThemeStateKey = 'bible-desktop-reader-theme';
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
const prayers = ref<PrayerDto[]>([]);
const isPrayersLoading = ref(false);
const prayersError = ref<string | null>(null);
const selectedPrayerId = ref<number | null>(null);
const selectedPrayer = ref<PrayerDto | null>(null);
const selectedPrayerSection = ref<PrayerSectionDto | null>(null);
const isPrayerLoading = ref(false);
const isPrayerSectionLoading = ref(false);
const prayerBodyCache = new Map<number, PrayerDto>();
const prayerSectionCache = new Map<number, PrayerSectionDto>();
const recipeCategories = ref<RecipeCategoryDto[]>([]);
const recipes = ref<RecipeDto[]>([]);
const selectedRecipeCategorySlug = ref('');
const selectedRecipe = ref<RecipeDto | null>(null);
const selectedRecipeServings = ref(4);
const usefulLinks = ref<UsefulLinkDto[]>([]);
const faithQuestions = ref<FaithQuestionDto[]>([]);
const selectedFaithQuestion = ref<FaithQuestionDto | null>(null);
const quizzes = ref<QuizDto[]>([]);
const selectedQuiz = ref<QuizDto | null>(null);
const selectedQuizQuestionIndex = ref(0);
const quizAnswers = ref<Record<number, number[] | string>>({});
const quizSubmitted = ref(false);
const virtualTours = ref<VirtualTourDto[]>([]);
const selectedVirtualTour = ref<VirtualTourDto | null>(null);
const isVirtualTourOverlayOpen = ref(false);
const contentToolsError = ref<string | null>(null);
const isContentToolsLoading = ref(false);
const mainContentMode = ref<MainContentMode>('chapter');
const bookmarks = ref<BookmarkItem[]>([]);
const viewHistory = ref<HistoryItem[]>([]);
const highlightedVerseNumbers = ref<number[]>([]);
const selectionAnchorVerseNumber = ref<number | null>(null);
const readerTabs = ref<ReaderTab[]>([]);
const activeTabId = ref('');
const activeStudyTab = ref<'strong' | 'references' | 'notes' | 'feed'>('strong');
const isStudyPanelOpen = ref(false);
const isReaderMenuOpen = ref(false);
const isMobileToolRailOpen = ref(false);
const socialPosts = ref<SocialPostDto[]>([]);
const socialPostBody = ref('');
const isSocialFeedLoading = ref(false);
const socialFeedError = ref<string | null>(null);
const socialFeedScope = ref<'book' | 'all'>('book');
const isSocialComposerOpen = ref(false);
const verseMenu = ref<{ verse: Verse; x: number; y: number } | null>(null);
const verseActionMessage = ref('');
const showStrongNumbers = ref(false);
const readerFontSize = ref(defaultReaderFontSize());
const readerTheme = ref<ReaderTheme>(defaultReaderTheme());
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

const leftTools = [
    { id: 'library', icon: 'library', title: 'Библиотека', group: 'reader' },
    { id: 'bookmarks', icon: 'bookmarks', title: 'Закладки', group: 'reader' },
    { id: 'history', icon: 'history', title: 'История просмотров', group: 'reader' },
    { id: 'print', icon: 'print', title: 'Печать страницы', group: 'reader' },
    { id: 'search', icon: 'strong', title: 'Поиск', group: 'reader' },
    { id: 'calendar', icon: 'calendar', title: 'Церковный календарь', group: 'church' },
    { id: 'prayers', icon: 'prayers', title: 'Молитвослов', group: 'church' },
    { id: 'faith', icon: 'faith', title: 'Вопросы веры', group: 'church' },
    { id: 'materials', icon: 'materials', title: 'Полезные материалы', group: 'church' },
    { id: 'recipes', icon: 'recipes', title: 'Постные рецепты', group: 'church' },
    { id: 'quizzes', icon: 'quizzes', title: 'Тесты', group: 'church' },
    { id: 'tours', icon: 'tours', title: 'Храмы и монастыри 360°', group: 'church' },
] satisfies Array<{ id: LeftPanelId | 'print'; icon: string; title: string; group: 'reader' | 'church' }>;

const studyTools = [
    { id: 'strong', icon: 'strong', title: 'Номера Стронга' },
    { id: 'references', icon: 'references', title: 'Параллельные места' },
    { id: 'notes', icon: 'comments', title: 'Толкования и комментарии' },
    { id: 'feed', icon: 'feed', title: 'Лента размышлений' },
] satisfies Array<{ id: StudyToolId | 'strong'; icon: string; title: string }>;

function defaultReaderFontSize(): number {
    if (typeof window === 'undefined') {
        return 15;
    }

    return window.matchMedia('(max-width: 760px)').matches ? 16 : 15;
}

function isCompactReaderViewport(): boolean {
    if (typeof window === 'undefined') {
        return false;
    }

    return window.matchMedia('(max-width: 760px), (max-width: 980px) and (max-height: 520px)').matches;
}

function defaultReaderTheme(): ReaderTheme {
    if (typeof window === 'undefined') {
        return 'light';
    }

    return window.localStorage.getItem(readerThemeStateKey) === 'dark' ? 'dark' : 'light';
}

function changeReaderFontSize(delta: number): void {
    readerFontSize.value = Math.min(22, Math.max(13, readerFontSize.value + delta));
}

function toggleReaderTheme(): void {
    readerTheme.value = readerTheme.value === 'dark' ? 'light' : 'dark';
}

function toolIconUrl(icon: string): string {
    return `/assets/tool-icons/${icon}.png`;
}

function closeReaderMenu(): void {
    isReaderMenuOpen.value = false;
}

function handleMobileToolClick(toolId: ToolId): void {
    handleToolClick(toolId);
    isMobileToolRailOpen.value = false;
}

function openStudyTool(toolId: StudyToolId | 'strong'): void {
    activeStudyTab.value = toolId === 'strong' ? 'strong' : toolId;
    isStudyPanelOpen.value = true;
    activeLeftPanel.value = null;
}

function openVerseStudyTool(toolId: StudyToolId | 'strong'): void {
    if (!selectedVerse.value) {
        return;
    }

    if (toolId === 'strong') {
        showStrongNumbers.value = !showStrongNumbers.value;
        hideStrongTooltip();

        if (!showStrongNumbers.value) {
            selectedStrongEntry.value = null;
        }

        return;
    }

    openStudyTool(toolId);
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
    feed: [
        'M5 6.5A1.5 1.5 0 0 1 6.5 5h11A1.5 1.5 0 0 1 19 6.5v11a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 5 17.5v-11Z',
        'M8 9h8',
        'M8 12h8',
        'M8 15h5',
    ],
    globe: [
        'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z',
        'M3.6 9h16.8',
        'M3.6 15h16.8',
        'M12 3c2.5 2.4 3.75 5.4 3.75 9S14.5 18.6 12 21',
        'M12 3c-2.5 2.4-3.75 5.4-3.75 9S9.5 18.6 12 21',
    ],
    hash: [
        'M5 9h14',
        'M4 15h14',
        'M10 3 8 21',
        'm16 3-2 18',
    ],
    link: [
        'M10 13a5 5 0 0 0 7.54.54l2-2A5 5 0 0 0 12.46 8',
        'M14 11a5 5 0 0 0-7.54-4.54l-2 2A5 5 0 0 0 11.54 16',
    ],
    menu: [
        'M4 6h16',
        'M4 12h16',
        'M4 18h16',
    ],
    note: [
        'M6 4.75C6 3.78 6.78 3 7.75 3h6.75L18 6.5v12.75c0 .97-.78 1.75-1.75 1.75h-8.5C6.78 21 6 20.22 6 19.25V4.75Z',
        'M14 3v4h4',
        'M9 11h6',
        'M9 15h6',
    ],
    plus: [
        'M12 5v14',
        'M5 12h14',
    ],
    prayer: [
        'M12 3v18',
        'M7 8h10',
        'M8.5 20h7',
        'M12 3c1.75 1.35 2.62 2.7 2.62 4.05 0 1.2-.98 2.2-2.62 3-1.64-.8-2.62-1.8-2.62-3C9.38 5.7 10.25 4.35 12 3Z',
    ],
    printer: [
        'M7 8V4h10v4',
        'M7 18h10v-5H7v5Z',
        'M6 14H5a2 2 0 0 1-2-2v-1a3 3 0 0 1 3-3h12a3 3 0 0 1 3 3v1a2 2 0 0 1-2 2h-1',
    ],
    recipe: [
        'M6 3h12',
        'M8 3v7a4 4 0 0 0 8 0V3',
        'M12 14v7',
        'M9 21h6',
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
    test: [
        'M9 9a3 3 0 1 1 5.1 2.13c-.9.83-1.6 1.36-1.6 2.87',
        'M12 17h.01',
        'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z',
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
const visibleRecipes = computed(() => {
    if (!selectedRecipeCategorySlug.value) {
        return recipes.value;
    }

    return recipes.value.filter((recipe) => recipe.category.slug === selectedRecipeCategorySlug.value);
});
const selectedRecipeBaseServings = computed(() => Math.max(1, selectedRecipe.value?.servings ?? 4));
const selectedRecipeTargetServings = computed(() => {
    const servings = Number(selectedRecipeServings.value);

    return Number.isFinite(servings) ? Math.max(1, Math.round(servings)) : selectedRecipeBaseServings.value;
});
const selectedRecipeServingRatio = computed(() => selectedRecipeTargetServings.value / selectedRecipeBaseServings.value);
const selectedQuizQuestions = computed(() => selectedQuiz.value?.questions ?? []);
const selectedQuizQuestion = computed(() => selectedQuizQuestions.value[selectedQuizQuestionIndex.value] ?? null);
const quizScorableQuestions = computed(() => {
    return selectedQuizQuestions.value.filter((question) => question.answer_type !== 'text');
});
const quizCorrectAnswerCount = computed(() => {
    return quizScorableQuestions.value.filter((question) => isQuizQuestionCorrect(question)).length;
});
const quizAnsweredCount = computed(() => {
    return selectedQuizQuestions.value.filter((question) => isQuizQuestionAnswered(question)).length;
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
const workspaceTitle = computed(() => activeReaderTab()?.title ?? currentTitle.value);
const visibleReaderTabs = computed(() => {
    return readerTabs.value.map((tab) => ({
        ...tab,
        title: tab.id === activeTabId.value && tab.contentMode === 'chapter' ? currentTitle.value : tab.title,
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

    if (activeLeftPanel.value === 'prayers') {
        return 'Молитвы';
    }

    if (activeLeftPanel.value === 'faith') {
        return 'Вопросы веры';
    }

    if (activeLeftPanel.value === 'materials') {
        return 'Полезные материалы';
    }

    if (activeLeftPanel.value === 'recipes') {
        return 'Постные рецепты';
    }

    if (activeLeftPanel.value === 'quizzes') {
        return 'Тесты';
    }

    if (activeLeftPanel.value === 'tours') {
        return '360° туры';
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
const printDocument = ref<PrintDocument>({
    title: '',
    subtitle: null,
    blocks: [],
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

function strongTooltipPosition(event: Event): { x: number; y: number } {
    const target = event.currentTarget instanceof HTMLElement ? event.currentTarget : null;
    const rect = target?.getBoundingClientRect();
    const rawX = rect ? rect.left + 10 : 12;
    const rawY = rect ? rect.bottom + 8 : 12;

    return {
        x: Math.max(12, Math.min(rawX, window.innerWidth - 292)),
        y: Math.max(12, Math.min(rawY, window.innerHeight - 150)),
    };
}

function showStrongTooltip(event: Event, verse: Verse, strongNumber: string): void {
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

async function showStrongTooltipNow(event: Event, verse: Verse, strongNumber: string): Promise<void> {
    hideStrongTooltip();

    const position = strongTooltipPosition(event);

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
}

function showStrongTooltipForPointer(event: Event, verse: Verse, strongNumber: string): void {
    if (isCompactReaderViewport()) {
        return;
    }

    showStrongTooltip(event, verse, strongNumber);
}

function hideStrongTooltipForPointer(): void {
    if (isCompactReaderViewport()) {
        return;
    }

    hideStrongTooltip();
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
    const contentMode = state.contentMode ?? 'chapter';
    const contentId = contentMode === 'chapter' ? null : state.contentId ?? null;

    return {
        id: state.id ?? createClientId(),
        title: state.title ?? formatStoredTabTitle(bookSlug, chapterNumber),
        translationCode,
        bookSlug,
        chapterNumber,
        contentMode,
        contentId,
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

            const contentMode = isMainContentMode(candidate.contentMode) ? candidate.contentMode : 'chapter';
            const contentId = typeof candidate.contentId === 'number' && candidate.contentId > 0
                ? candidate.contentId
                : null;

            if (contentMode !== 'chapter' && contentId === null) {
                return null;
            }

            return createReaderTab({
                id: typeof candidate.id === 'string' && candidate.id !== '' ? candidate.id : undefined,
                title: typeof candidate.title === 'string' && candidate.title !== '' ? candidate.title : undefined,
                translationCode: candidate.translationCode,
                bookSlug: candidate.bookSlug,
                chapterNumber: Number(candidate.chapterNumber),
                contentMode,
                contentId,
            });
        })
        .filter((tab): tab is ReaderTab => tab !== null)
        .slice(0, maxReaderTabs);
}

function isMainContentMode(value: unknown): value is MainContentMode {
    return value === 'chapter'
        || value === 'prayer'
        || value === 'faith-question'
        || value === 'recipe'
        || value === 'quiz'
        || value === 'tour';
}

function activeReaderTab(): ReaderTab | null {
    return readerTabs.value.find((tab) => tab.id === activeTabId.value) ?? readerTabs.value[0] ?? null;
}

function syncActiveTabFromSelection(): void {
    const tab = activeReaderTab();

    if (!tab || tab.contentMode !== 'chapter') {
        return;
    }

    tab.translationCode = selectedTranslationCode.value;
    tab.bookSlug = selectedBookSlug.value;
    tab.chapterNumber = selectedChapterNumber.value;
    tab.title = currentTitle.value;
}

function setActiveTabContent(mode: MainContentMode, title: string | null = null, contentId: number | null = null): void {
    const tab = activeReaderTab();

    if (!tab) {
        return;
    }

    tab.contentMode = mode;
    tab.title = title ?? (mode === 'chapter' ? currentTitle.value : tab.title);
    tab.contentId = mode === 'chapter' ? null : contentId ?? tab.contentId;
}

function openContentTab(mode: Exclude<MainContentMode, 'chapter'>, title: string, contentId: number): void {
    syncActiveTabFromSelection();

    const tab = activeReaderTab();
    if (!tab || tab.contentMode === 'chapter' || tab.contentMode !== mode) {
        if (readerTabs.value.length < maxReaderTabs) {
            const contentTab = createReaderTab({
                title,
                contentMode: mode,
                translationCode: selectedTranslationCode.value,
                bookSlug: selectedBookSlug.value,
                chapterNumber: selectedChapterNumber.value,
                contentId,
            });
            readerTabs.value.push(contentTab);
            activeTabId.value = contentTab.id;
        }
    }

    mainContentMode.value = mode;
    setActiveTabContent(mode, title, contentId);
    isStudyPanelOpen.value = false;
    closeReaderMenu();
    persistReaderState();
}

async function restoreContentTab(tab: ReaderTab): Promise<void> {
    if (tab.contentMode === 'chapter' || tab.contentId === null) {
        return;
    }

    mainContentMode.value = tab.contentMode;
    isStudyPanelOpen.value = false;
    closeReaderMenu();
    contentToolsError.value = null;

    try {
        if (tab.contentMode === 'prayer') {
            const cached = prayerBodyCache.get(tab.contentId);
            const prayer = cached
                ?? (await loadJson<ApiResponse<PrayerDto>>(`/api/prayers/${tab.contentId}`)).data;

            prayerBodyCache.set(prayer.id, prayer);
            selectedPrayerId.value = prayer.id;
            selectedPrayer.value = prayer;
            selectedPrayerSection.value = null;
            setActiveTabContent('prayer', prayer.title, prayer.id);

            if (prayer.sections?.[0]) {
                await selectPrayerSection(prayer.sections[0]);
            }
            return;
        }

        if (tab.contentMode === 'recipe') {
            const recipe = (await loadJson<ApiResponse<RecipeDto>>(`/api/recipes/${tab.contentId}`)).data;
            selectedRecipe.value = recipe;
            selectedRecipeServings.value = Math.max(1, recipe.servings ?? 4);
            setActiveTabContent('recipe', recipe.title, recipe.id);
            return;
        }

        if (tab.contentMode === 'quiz') {
            const quiz = (await loadJson<ApiResponse<QuizDto>>(`/api/quizzes/${tab.contentId}`)).data;
            selectedQuiz.value = quiz;
            selectedQuizQuestionIndex.value = 0;
            quizAnswers.value = {};
            quizSubmitted.value = false;
            setActiveTabContent('quiz', quiz.title, quiz.id);
            return;
        }

        if (tab.contentMode === 'faith-question') {
            await loadFaithQuestions();
            const question = faithQuestions.value.find((item) => item.id === tab.contentId) ?? null;
            selectedFaithQuestion.value = question;

            if (question) {
                setActiveTabContent('faith-question', question.question, question.id);
            }
            return;
        }

        await loadVirtualTours();
        const tour = virtualTours.value.find((item) => item.id === tab.contentId) ?? null;
        selectedVirtualTour.value = tour;
        isVirtualTourOverlayOpen.value = false;

        if (tour) {
            setActiveTabContent('tour', tour.title, tour.id);
        }
    } catch (error) {
        contentToolsError.value = error instanceof Error ? error.message : 'Не удалось восстановить содержимое вкладки';
    }
}

function ensureChapterTab(title: string | null = null): void {
    const tab = activeReaderTab();

    if (tab && tab.contentMode !== 'chapter' && readerTabs.value.length < maxReaderTabs) {
        const chapterTab = createReaderTab({
            title: title ?? currentTitle.value,
            contentMode: 'chapter',
            translationCode: selectedTranslationCode.value,
            bookSlug: selectedBookSlug.value,
            chapterNumber: selectedChapterNumber.value,
        });
        readerTabs.value.push(chapterTab);
        activeTabId.value = chapterTab.id;
    }

    mainContentMode.value = 'chapter';
    setActiveTabContent('chapter', title, null);
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

function persistReaderTheme(): void {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(readerThemeStateKey, readerTheme.value);
}

function toggleLeftPanel(panel: LeftPanelId): void {
    activeLeftPanel.value = activeLeftPanel.value === panel ? null : panel;
    if (activeLeftPanel.value !== null) {
        isStudyPanelOpen.value = false;
    }

    if (activeLeftPanel.value === 'calendar') {
        void loadCalendarDay();
    }

    if (activeLeftPanel.value === 'prayers') {
        void loadPrayers();
    }

    if (activeLeftPanel.value === 'faith') {
        void loadFaithQuestions();
    }

    if (activeLeftPanel.value === 'materials') {
        void loadUsefulLinks();
    }

    if (activeLeftPanel.value === 'recipes') {
        void loadRecipeTools();
    }

    if (activeLeftPanel.value === 'quizzes') {
        void loadQuizzes();
    }

    if (activeLeftPanel.value === 'tours') {
        void loadVirtualTours();
    }
}

function handleToolClick(toolId: ToolId): void {
    if (toolId === 'library' || toolId === 'calendar' || toolId === 'bookmarks' || toolId === 'history' || toolId === 'search' || toolId === 'prayers' || toolId === 'faith' || toolId === 'materials' || toolId === 'recipes' || toolId === 'quizzes' || toolId === 'tours') {
        toggleLeftPanel(toolId);
        return;
    }

    if (toolId === 'references' || toolId === 'notes' || toolId === 'feed') {
        activeStudyTab.value = toolId;
        isStudyPanelOpen.value = true;
        activeLeftPanel.value = null;
        return;
    }

    if (toolId === 'print') {
        printPage();
        return;
    }

    if (toolId === 'strong') {
        showStrongNumbers.value = !showStrongNumbers.value;
        activeStudyTab.value = 'strong';
        isStudyPanelOpen.value = true;
        activeLeftPanel.value = null;
    }
}

async function loadPrayers(): Promise<void> {
    if (prayers.value.length > 0 || isPrayersLoading.value) {
        return;
    }

    isPrayersLoading.value = true;
    prayersError.value = null;

    try {
        const language = selectedLanguage.value === 'Русский' ? 'ru' : 'ru';
        const response = await loadJson<ApiResponse<PrayerDto[]>>(`/api/prayers?language=${encodeURIComponent(language)}`);
        prayers.value = response.data;
    } catch (error) {
        prayersError.value = error instanceof Error ? error.message : 'Не удалось загрузить молитвы';
    } finally {
        isPrayersLoading.value = false;
    }
}

async function selectPrayer(prayer: PrayerDto): Promise<void> {
    openContentTab('prayer', prayer.title, prayer.id);
    selectedPrayerId.value = prayer.id;
    prayersError.value = null;

    const cached = prayerBodyCache.get(prayer.id);
    if (cached) {
        selectedPrayer.value = cached;
        mainContentMode.value = 'prayer';
        setActiveTabContent('prayer', cached.title, cached.id);
        return;
    }

    selectedPrayer.value = prayer;
    isPrayerLoading.value = true;

    try {
        const response = await loadJson<ApiResponse<PrayerDto>>(`/api/prayers/${prayer.id}`);
        selectedPrayer.value = response.data;
        prayerBodyCache.set(prayer.id, response.data);
        mainContentMode.value = 'prayer';
        setActiveTabContent('prayer', response.data.title, response.data.id);
        if (response.data.sections && response.data.sections.length > 0) {
            void selectPrayerSection(response.data.sections[0]);
        }
    } catch (error) {
        prayersError.value = error instanceof Error ? error.message : 'Не удалось загрузить текст молитвы';
    } finally {
        isPrayerLoading.value = false;
    }
}

async function selectPrayerSection(section: PrayerSectionDto): Promise<void> {
    selectedPrayerSection.value = section;
    const cached = prayerSectionCache.get(section.id);
    if (cached) {
        selectedPrayerSection.value = cached;
        return;
    }

    if (!selectedPrayer.value) {
        return;
    }

    isPrayerSectionLoading.value = true;
    try {
        const response = await loadJson<ApiResponse<PrayerSectionDto>>(`/api/prayers/${selectedPrayer.value.id}/sections/${section.id}`);
        selectedPrayerSection.value = response.data;
        prayerSectionCache.set(section.id, response.data);
    } catch (error) {
        prayersError.value = error instanceof Error ? error.message : 'Не удалось загрузить часть молитвы';
    } finally {
        isPrayerSectionLoading.value = false;
    }
}

async function loadUsefulLinks(): Promise<void> {
    if (usefulLinks.value.length > 0 || isContentToolsLoading.value) {
        return;
    }

    isContentToolsLoading.value = true;
    contentToolsError.value = null;

    try {
        const response = await loadJson<ApiResponse<UsefulLinkDto[]>>('/api/useful-links');
        usefulLinks.value = response.data;
    } catch (error) {
        contentToolsError.value = error instanceof Error ? error.message : 'Не удалось загрузить материалы';
    } finally {
        isContentToolsLoading.value = false;
    }
}

async function loadFaithQuestions(): Promise<void> {
    if (faithQuestions.value.length > 0 || isContentToolsLoading.value) {
        return;
    }

    isContentToolsLoading.value = true;
    contentToolsError.value = null;

    try {
        const response = await loadJson<ApiResponse<FaithQuestionDto[]>>('/api/faith-questions');
        faithQuestions.value = response.data;
    } catch (error) {
        contentToolsError.value = error instanceof Error ? error.message : 'Не удалось загрузить вопросы веры';
    } finally {
        isContentToolsLoading.value = false;
    }
}

function selectFaithQuestion(question: FaithQuestionDto): void {
    openContentTab('faith-question', question.question, question.id);
    selectedFaithQuestion.value = question;
    mainContentMode.value = 'faith-question';
    setActiveTabContent('faith-question', question.question, question.id);
}

async function loadRecipeTools(): Promise<void> {
    if ((recipeCategories.value.length > 0 && recipes.value.length > 0) || isContentToolsLoading.value) {
        return;
    }

    isContentToolsLoading.value = true;
    contentToolsError.value = null;
    try {
        const [categoriesResponse, recipesResponse] = await Promise.all([
            loadJson<ApiResponse<RecipeCategoryDto[]>>('/api/recipe-categories'),
            loadJson<ApiResponse<RecipeDto[]>>('/api/recipes'),
        ]);
        recipeCategories.value = categoriesResponse.data;
        recipes.value = recipesResponse.data;
    } catch (error) {
        contentToolsError.value = error instanceof Error ? error.message : 'Не удалось загрузить рецепты';
    } finally {
        isContentToolsLoading.value = false;
    }
}

function openFastingRecipes(): void {
    activeLeftPanel.value = 'recipes';
    selectedRecipeCategorySlug.value = 'postnye-retsepty';
    isStudyPanelOpen.value = false;
    void loadRecipeTools();
}

async function selectRecipe(recipe: RecipeDto): Promise<void> {
    openContentTab('recipe', recipe.title, recipe.id);
    mainContentMode.value = 'recipe';
    selectedRecipe.value = recipe;
    selectedRecipeServings.value = Math.max(1, recipe.servings ?? 4);
    contentToolsError.value = null;

    try {
        const response = await loadJson<ApiResponse<RecipeDto>>(`/api/recipes/${recipe.id}`);
        selectedRecipe.value = response.data;
        selectedRecipeServings.value = Math.max(1, response.data.servings ?? 4);
        setActiveTabContent('recipe', response.data.title, response.data.id);
    } catch (error) {
        contentToolsError.value = error instanceof Error ? error.message : 'Не удалось загрузить рецепт';
    }
}

function scaledIngredientAmount(amount: number | null): string {
    if (amount === null) {
        return '';
    }

    const scaled = amount * selectedRecipeServingRatio.value;
    const rounded = Math.round(scaled * 100) / 100;

    return Number.isInteger(rounded) ? String(rounded) : rounded.toFixed(2).replace(/0+$/, '').replace(/\.$/, '').replace('.', ',');
}

function recipeIngredientLine(ingredient: NonNullable<RecipeDto['ingredient_items']>[number]): string {
    const amount = scaledIngredientAmount(ingredient.amount);
    const unit = ingredient.unit ? ingredient.unit : '';
    const note = ingredient.note ? ` (${ingredient.note})` : '';
    const quantity = [amount, unit].filter(Boolean).join(' ');

    return `${ingredient.name}${quantity ? ` - ${quantity}` : ''}${note}`;
}

async function loadQuizzes(): Promise<void> {
    if (quizzes.value.length > 0 || isContentToolsLoading.value) {
        return;
    }

    isContentToolsLoading.value = true;
    contentToolsError.value = null;
    try {
        const response = await loadJson<ApiResponse<QuizDto[]>>('/api/quizzes');
        quizzes.value = response.data;
    } catch (error) {
        contentToolsError.value = error instanceof Error ? error.message : 'Не удалось загрузить тесты';
    } finally {
        isContentToolsLoading.value = false;
    }
}

async function selectQuiz(quiz: QuizDto): Promise<void> {
    openContentTab('quiz', quiz.title, quiz.id);
    mainContentMode.value = 'quiz';
    selectedQuiz.value = quiz;
    selectedQuizQuestionIndex.value = 0;
    quizAnswers.value = {};
    quizSubmitted.value = false;
    contentToolsError.value = null;
    try {
        const response = await loadJson<ApiResponse<QuizDto>>(`/api/quizzes/${quiz.id}`);
        selectedQuiz.value = response.data;
        setActiveTabContent('quiz', response.data.title, response.data.id);
    } catch (error) {
        contentToolsError.value = error instanceof Error ? error.message : 'Не удалось загрузить тест';
    }
}

function quizQuestionTypeLabel(question: QuizQuestionDto): string {
    if (question.answer_type === 'multiple') {
        return 'Можно выбрать несколько ответов';
    }

    if (question.answer_type === 'text') {
        return 'Ответ своими словами';
    }

    if (question.answer_type === 'yes_no') {
        return 'Да / Нет';
    }

    if (question.answer_type === 'scale') {
        return 'Оценка по шкале';
    }

    return 'Один ответ';
}

function selectedQuizAnswerIds(question: QuizQuestionDto): number[] {
    const answer = quizAnswers.value[question.id];

    return Array.isArray(answer) ? answer : [];
}

function quizTextAnswer(question: QuizQuestionDto): string {
    const answer = quizAnswers.value[question.id];

    return typeof answer === 'string' ? answer : '';
}

function isQuizAnswerSelected(question: QuizQuestionDto, answer: QuizAnswerDto): boolean {
    return selectedQuizAnswerIds(question).includes(answer.id);
}

function toggleQuizAnswer(question: QuizQuestionDto, answer: QuizAnswerDto): void {
    if (quizSubmitted.value) {
        return;
    }

    if (question.answer_type === 'multiple') {
        const selected = selectedQuizAnswerIds(question);
        quizAnswers.value[question.id] = selected.includes(answer.id)
            ? selected.filter((answerId) => answerId !== answer.id)
            : [...selected, answer.id];
        return;
    }

    quizAnswers.value[question.id] = [answer.id];
}

function updateQuizTextAnswer(question: QuizQuestionDto, event: Event): void {
    quizAnswers.value[question.id] = (event.target as HTMLTextAreaElement).value;
}

function isQuizQuestionAnswered(question: QuizQuestionDto): boolean {
    if (question.answer_type === 'text') {
        return quizTextAnswer(question).trim().length > 0;
    }

    return selectedQuizAnswerIds(question).length > 0;
}

function isQuizQuestionCorrect(question: QuizQuestionDto): boolean {
    if (question.answer_type === 'text') {
        return false;
    }

    const selected = [...selectedQuizAnswerIds(question)].sort((left, right) => left - right);
    const correct = question.answers
        .filter((answer) => answer.is_correct)
        .map((answer) => answer.id)
        .sort((left, right) => left - right);

    return selected.length === correct.length && selected.every((answerId, index) => answerId === correct[index]);
}

function quizAnswerClass(question: QuizQuestionDto, answer: QuizAnswerDto): Record<string, boolean> {
    const selected = isQuizAnswerSelected(question, answer);

    return {
        selected,
        correct: quizSubmitted.value && answer.is_correct,
        wrong: quizSubmitted.value && selected && !answer.is_correct,
    };
}

function goToQuizQuestion(index: number): void {
    selectedQuizQuestionIndex.value = Math.min(Math.max(index, 0), Math.max(selectedQuizQuestions.value.length - 1, 0));
}

function submitQuiz(): void {
    quizSubmitted.value = true;
}

function resetQuiz(): void {
    selectedQuizQuestionIndex.value = 0;
    quizAnswers.value = {};
    quizSubmitted.value = false;
}

async function loadVirtualTours(): Promise<void> {
    if (virtualTours.value.length > 0 || isContentToolsLoading.value) {
        return;
    }

    isContentToolsLoading.value = true;
    contentToolsError.value = null;
    try {
        const response = await loadJson<ApiResponse<VirtualTourDto[]>>('/api/virtual-tours');
        virtualTours.value = response.data;
    } catch (error) {
        contentToolsError.value = error instanceof Error ? error.message : 'Не удалось загрузить 360° туры';
    } finally {
        isContentToolsLoading.value = false;
    }
}

function selectVirtualTour(tour: VirtualTourDto): void {
    openContentTab('tour', tour.title, tour.id);
    selectedVirtualTour.value = tour;
    isVirtualTourOverlayOpen.value = true;
    setActiveTabContent('tour', tour.title, tour.id);
}

function closeVirtualTourOverlay(): void {
    isVirtualTourOverlayOpen.value = false;
}

function prayerCategoryLabel(category: string): string {
    return {
        common: 'Основные',
        morning: 'Утренние',
        evening: 'Вечерние',
        communion_before: 'Ко Святому Причащению',
        communion_after: 'После Святого Причащения',
        day: 'В продолжение дня',
        other: 'Другое',
    }[category] ?? category;
}

function typiconIconUrl(event: CalendarEventDto): string | null {
    const configuredIcon = event.type?.typicon_symbol?.trim();

    if (configuredIcon && /^[1-5]$/.test(configuredIcon)) {
        return `/images/typicon/${configuredIcon}.svg`;
    }

    const icon = {
        0: '1',
        1: '1',
        2: '1',
        3: '2',
        4: '3',
        5: '4',
        6: '5',
    }[event.legacy_type ?? -1];

    return icon ? `/images/typicon/${icon}.svg` : null;
}

function typiconTitle(event: CalendarEventDto): string {
    return event.type?.name ?? 'Знак типикона';
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

function currentPrintDocument(): PrintDocument {
    if (mainContentMode.value === 'recipe' && selectedRecipe.value) {
        return recipePrintDocument(selectedRecipe.value);
    }

    if (mainContentMode.value === 'prayer' && selectedPrayer.value) {
        return prayerPrintDocument(selectedPrayer.value);
    }

    return chapterPrintDocument();
}

function recipePrintDocument(recipe: RecipeDto): PrintDocument {
    const blocks: PrintBlock[] = [];

    if (recipe.summary) {
        blocks.push({ title: 'Описание', body: recipe.summary });
    }

    if (recipe.ingredient_items?.length) {
        blocks.push({
            title: `Ингредиенты на ${selectedRecipeTargetServings.value} порц.`,
            body: recipe.ingredient_items.map((ingredient) => recipeIngredientLine(ingredient)).join('\n'),
        });
    } else if (recipe.ingredients) {
        blocks.push({ title: 'Ингредиенты', body: recipe.ingredients });
    }

    if (recipe.steps?.length) {
        recipe.steps.forEach((step) => {
            blocks.push({
                title: `Шаг ${step.step_number}`,
                body: step.body,
                image: step.image_url,
            });
        });
    }

    if (recipe.youtube_url) {
        blocks.push({ title: 'Видео', body: recipe.youtube_url });
    }

    return {
        title: recipe.title,
        subtitle: recipe.category.name,
        blocks,
    };
}

function prayerPrintDocument(prayer: PrayerDto): PrintDocument {
    const body = selectedPrayerSection.value?.body || prayer.body || prayer.intro || '';
    const sectionTitle = selectedPrayerSection.value?.title;

    return {
        title: prayer.title,
        subtitle: sectionTitle || prayerCategoryLabel(prayer.category),
        blocks: [
            {
                title: sectionTitle || 'Текст молитвы',
                html: body,
            },
        ],
    };
}

function chapterPrintDocument(): PrintDocument {
    const verses = selectedVerses.value.length > 0 ? selectedVerses.value : currentVerses.value;
    let title = currentTitle.value;

    if (verses.length === 1) {
        title = `${currentTitle.value}:${verses[0]?.number}`;
    } else if (selectedVerses.value.length > 1) {
        const lastSelectedVerse = selectedVerses.value[selectedVerses.value.length - 1];
        title = `${currentTitle.value}:${selectedVerses.value[0]?.number}-${lastSelectedVerse?.number}`;
    }

    return {
        title,
        subtitle: shortTranslationLabel(currentTranslation.value),
        blocks: verses.map((verse) => ({
            title: String(verse.number),
            body: displayVerseText(verse),
            kind: 'verse',
        })),
    };
}

function printPage(): void {
    printDocument.value = currentPrintDocument();
    window.setTimeout(() => window.print(), 0);
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
    ensureChapterTab();
    resetVerseSelection();
    await loadBooksForSelectedTranslation();
    await loadChapter();
    syncActiveTabFromSelection();
    persistReaderState();
}

function changeBook(): void {
    selectedChapterNumber.value = 1;
    ensureChapterTab();
    resetVerseSelection();
    void loadChapter();
}

function changeCompareTranslation(): void {
    void loadCompareChapter();
    persistReaderState();
}

function changeChapter(): void {
    ensureChapterTab();
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
    ensureChapterTab();
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
    syncActiveTabFromSelection();
    selectedTranslationCode.value = bookmark.translationCode;
    selectedBookSlug.value = bookmark.bookSlug;
    selectedChapterNumber.value = bookmark.chapterNumber;
    ensureChapterTab(bookmark.reference);
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
    syncActiveTabFromSelection();
    selectedTranslationCode.value = item.translationCode;
    selectedBookSlug.value = item.bookSlug;
    selectedChapterNumber.value = item.chapterNumber;
    ensureChapterTab(item.title);
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
    const isSameTranslation = translation.code === selectedTranslationCode.value;
    const isChapterTab = activeReaderTab()?.contentMode === 'chapter';

    if (isSameTranslation && isChapterTab) {
        activeLeftPanel.value = null;
        return;
    }

    syncActiveTabFromSelection();
    selectedTranslationCode.value = translation.code;
    selectedChapterNumber.value = 1;
    ensureChapterTab(translation.name);
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
    mainContentMode.value = tab.contentMode;

    if (tab.contentMode !== 'chapter') {
        await restoreContentTab(tab);
        persistReaderState();
        return;
    }

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
    mainContentMode.value = nextTab.contentMode;

    if (nextTab.contentMode !== 'chapter') {
        await restoreContentTab(nextTab);
        persistReaderState();
        return;
    }

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

async function selectInlineStrongNumber(
    verse: Verse,
    strongNumber: string,
    token: StrongTokenDto | null = null,
    event: Event | null = null,
): Promise<void> {
    const previousVerseId = selectedVerse.value?.id;
    const resolvedStrongNumber = token?.strong_number ?? strongNumber;

    selectedVerse.value = verse;
    highlightedVerseNumbers.value = [verse.number];
    studyReturnVerseNumber.value = verse.number;
    activeStudyTab.value = 'strong';

    if (verse.id && verse.id !== previousVerseId) {
        await loadStudyData(verse);
    }

    if (isCompactReaderViewport()) {
        isStudyPanelOpen.value = false;

        if (event) {
            await showStrongTooltipNow(event, verse, resolvedStrongNumber);
        }

        return;
    }

    await selectStrongNumber(resolvedStrongNumber, verse);
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
        const params = new URLSearchParams();

        if (socialFeedScope.value === 'book' && selectedBookSlug.value) {
            params.set('book', selectedBookSlug.value);
        }

        const url = params.toString() ? `/reader/feed?${params.toString()}` : '/reader/feed';
        const response = await loadJson<ApiResponse<{ posts: SocialPostDto[] }>>(url);
        socialPosts.value = response.data.posts;
    } catch (error) {
        socialPosts.value = [];
        socialFeedError.value = error instanceof Error ? error.message : 'Не удалось загрузить ленту';
    } finally {
        isSocialFeedLoading.value = false;
    }
}

async function openSocialPost(post: SocialPostDto): Promise<void> {
    if (!post.book_slug || !post.chapter_number || !post.verse_number) {
        return;
    }

    syncActiveTabFromSelection();

    selectedBookSlug.value = post.book_slug;
    selectedChapterNumber.value = post.chapter_number;
    ensureChapterTab(`${post.book_slug} ${post.chapter_number}:${post.verse_number}`);
    highlightedVerseNumbers.value = [post.verse_number];

    await loadBooksForSelectedTranslation();
    await loadChapter(post.verse_number);
    syncActiveTabFromSelection();
    persistReaderState();
}

function openSocialComposer(): void {
    if (!currentUser.value) {
        return;
    }

    isSocialComposerOpen.value = true;
}

function closeSocialComposer(): void {
    if (isSocialFeedLoading.value) {
        return;
    }

    isSocialComposerOpen.value = false;
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
        isSocialComposerOpen.value = false;
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
    syncActiveTabFromSelection();
    selectedBookSlug.value = result.book.slug;
    selectedChapterNumber.value = result.chapter_number;
    ensureChapterTab(searchResultReference(result));
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
    ensureChapterTab(crossReferenceLabel(reference));
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
            mainContentMode.value = initialTab.contentMode;
        }

        if (initialTab && initialTab.contentMode !== 'chapter') {
            await restoreContentTab(initialTab);
        } else {
            await loadBooksForSelectedTranslation();
            if (!books.value.some((book) => book.slug === selectedBookSlug.value)) {
                selectedBookSlug.value = books.value.find((book) => book.slug === 'genesis')?.slug ?? books.value[0]?.slug ?? 'genesis';
            }
            await loadChapter(highlightedVerseNumbers.value[0] ?? null);
            syncActiveTabFromSelection();
        }
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

watch(readerTheme, () => {
    persistReaderTheme();
});

watch(activeStudyTab, (tab) => {
    if (tab === 'feed') {
        void loadSocialFeed();
    }
});

watch([selectedBookSlug, socialFeedScope], () => {
    if (activeStudyTab.value === 'feed') {
        void loadSocialFeed();
    }
});
</script>

<template>
    <div class="app-shell" :class="[{ 'embed-shell': isEmbed }, `reader-theme-${readerTheme}`]" :style="readerStyle">
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

            <div class="reader-preferences" aria-label="Настройки чтения">
                <button
                    type="button"
                    class="mobile-search-button"
                    data-tooltip="Поиск"
                    aria-label="Открыть поиск"
                    @click="activeLeftPanel = 'search'; advancedSearchQuery = searchQuery; showSearchResults = false"
                >
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <path
                            v-for="path in iconPaths('search')"
                            :key="path"
                            :d="path"
                        />
                    </svg>
                </button>
                <button type="button" data-tooltip="Уменьшить шрифт" aria-label="Уменьшить шрифт" @click="changeReaderFontSize(-1)">A-</button>
                <button type="button" data-tooltip="Увеличить шрифт" aria-label="Увеличить шрифт" @click="changeReaderFontSize(1)">A+</button>
                <button type="button" :data-tooltip="readerTheme === 'dark' ? 'Светлая тема' : 'Тёмная тема'" aria-label="Переключить тему" @click="toggleReaderTheme">
                    {{ readerTheme === 'dark' ? '☀' : '☾' }}
                </button>
                <select aria-label="Язык интерфейса" disabled>
                    <option>RU</option>
                </select>
            </div>

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
            <span class="muted-icon">{{ mainContentMode === 'chapter' ? 'Чтение' : 'Открыто' }}</span>
            <strong>{{ workspaceTitle }}</strong>
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

        <main class="reader-layout" :class="{ 'has-left-panel': activeLeftPanel !== null, 'mobile-tools-open': isMobileToolRailOpen }">
            <button
                type="button"
                class="mobile-tool-toggle"
                :class="{ open: isMobileToolRailOpen }"
                :aria-expanded="isMobileToolRailOpen"
                aria-controls="reader-tool-rail"
                :aria-label="isMobileToolRailOpen ? 'Скрыть инструменты' : 'Показать инструменты'"
                @click="isMobileToolRailOpen = !isMobileToolRailOpen"
            >
                {{ isMobileToolRailOpen ? '↓' : '↑' }}
            </button>

            <aside id="reader-tool-rail" class="tool-rail" aria-label="Инструменты">
                <button
                    v-for="tool in leftTools"
                    :key="tool.id"
                    type="button"
                    :data-tooltip="tool.title"
                    :class="[
                        `tool-group-${tool.group}`,
                        { active: activeLeftPanel === tool.id },
                    ]"
                    @click="handleMobileToolClick(tool.id)"
                >
                    <img :src="toolIconUrl(tool.icon)" :alt="tool.title" />
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
                                <img
                                    v-if="typiconIconUrl(event)"
                                    class="calendar-event-icon"
                                    :src="typiconIconUrl(event) || ''"
                                    :alt="typiconTitle(event)"
                                    :title="typiconTitle(event)"
                                />
                                <span
                                    v-else-if="event.type?.typicon_symbol"
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
                        <template v-if="calendarDay.fasting_events.length > 0">
                            <h3>Пост</h3>
                            <ul>
                                <li v-for="event in calendarDay.fasting_events" :key="`fast-${event.id}`">
                                    {{ event.name }}
                                    <small v-if="event.metadata?.meal_note">{{ event.metadata.meal_note }}</small>
                                </li>
                            </ul>
                            <button type="button" class="calendar-reading-link" @click="openFastingRecipes">
                                Постные рецепты
                            </button>
                        </template>
                        <template v-if="calendarDay.monastery_services.length > 0">
                            <h3>Богослужения в монастыре</h3>
                            <ul class="service-list">
                                <li v-for="service in calendarDay.monastery_services" :key="service.id">
                                    <strong>{{ service.time_label }}</strong>
                                    <span>{{ service.title }}</span>
                                    <small v-if="service.description">{{ service.description }}</small>
                                </li>
                            </ul>
                        </template>
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

                <section v-else-if="activeLeftPanel === 'prayers'" class="prayer-panel">
                    <p v-if="isPrayersLoading">Загружаю молитвы...</p>
                    <p v-else-if="prayersError">{{ prayersError }}</p>
                    <div class="compact-list">
                        <button
                            v-for="prayer in prayers"
                            :key="prayer.id"
                            type="button"
                            :class="{ active: selectedPrayerId === prayer.id }"
                            @click="selectPrayer(prayer)"
                        >
                            <strong>{{ prayer.short_title || prayer.title }}</strong>
                            <small>{{ prayerCategoryLabel(prayer.category) }}</small>
                            <span>{{ prayer.excerpt }}</span>
                        </button>
                        <p v-if="!isPrayersLoading && prayers.length === 0">Молитвы пока не добавлены.</p>
                    </div>
                </section>

                <section v-else-if="activeLeftPanel === 'materials'" class="materials-panel">
                    <p v-if="isContentToolsLoading">Загружаю материалы...</p>
                    <p v-else-if="contentToolsError">{{ contentToolsError }}</p>
                    <div class="materials-card-list">
                        <a
                            v-for="link in usefulLinks"
                            :key="link.id"
                            :href="link.url"
                            target="_blank"
                            rel="noreferrer"
                        >
                            <img v-if="link.cover_image_url" :src="link.cover_image_url" alt="" />
                            <span v-else class="material-cover-fallback">{{ link.icon || 'link' }}</span>
                            <span class="material-card-body">
                                <strong>{{ link.title }}</strong>
                                <small>{{ link.category }}</small>
                                <span>{{ link.description }}</span>
                            </span>
                        </a>
                        <p v-if="!isContentToolsLoading && usefulLinks.length === 0">Материалы пока не добавлены.</p>
                    </div>
                </section>

                <section v-else-if="activeLeftPanel === 'faith'" class="faith-panel">
                    <p v-if="isContentToolsLoading">Загружаю вопросы веры...</p>
                    <p v-else-if="contentToolsError">{{ contentToolsError }}</p>
                    <div class="compact-list">
                        <button
                            v-for="question in faithQuestions"
                            :key="question.id"
                            type="button"
                            :class="{ active: selectedFaithQuestion?.id === question.id }"
                            @click="selectFaithQuestion(question)"
                        >
                            <strong>{{ question.question }}</strong>
                            <small>{{ question.category }}</small>
                        </button>
                        <p v-if="!isContentToolsLoading && faithQuestions.length === 0">Вопросов пока нет.</p>
                    </div>
                </section>

                <section v-else-if="activeLeftPanel === 'recipes'" class="recipe-panel">
                    <p v-if="isContentToolsLoading">Загружаю рецепты...</p>
                    <p v-else-if="contentToolsError">{{ contentToolsError }}</p>
                    <div class="category-filter">
                        <button
                            type="button"
                            :class="{ active: selectedRecipeCategorySlug === '' }"
                            @click="selectedRecipeCategorySlug = ''"
                        >
                            Все
                        </button>
                        <button
                            v-for="category in recipeCategories"
                            :key="category.id"
                            type="button"
                            :class="{ active: selectedRecipeCategorySlug === category.slug }"
                            @click="selectedRecipeCategorySlug = category.slug"
                        >
                            {{ category.name }}
                        </button>
                    </div>
                    <div class="compact-list">
                        <button
                            v-for="recipe in visibleRecipes"
                            :key="recipe.id"
                            type="button"
                            :class="{ active: selectedRecipe?.id === recipe.id }"
                            @click="selectRecipe(recipe)"
                        >
                            <strong>{{ recipe.title }}</strong>
                            <small>{{ recipe.category.name }}</small>
                            <span>{{ recipe.summary }}</span>
                        </button>
                        <p v-if="!isContentToolsLoading && visibleRecipes.length === 0">Рецептов пока нет.</p>
                    </div>
                </section>

                <section v-else-if="activeLeftPanel === 'quizzes'" class="quiz-panel">
                    <p v-if="isContentToolsLoading">Загружаю тесты...</p>
                    <p v-else-if="contentToolsError">{{ contentToolsError }}</p>
                    <div class="compact-list">
                        <button
                            v-for="quiz in quizzes"
                            :key="quiz.id"
                            type="button"
                            :class="{ active: selectedQuiz?.id === quiz.id }"
                            @click="selectQuiz(quiz)"
                        >
                            <strong>{{ quiz.title }}</strong>
                            <span>{{ quiz.description }}</span>
                        </button>
                        <p v-if="!isContentToolsLoading && quizzes.length === 0">Тестов пока нет.</p>
                    </div>
                </section>

                <section v-else-if="activeLeftPanel === 'tours'" class="tour-panel">
                    <p v-if="isContentToolsLoading">Загружаю туры...</p>
                    <p v-else-if="contentToolsError">{{ contentToolsError }}</p>
                    <div class="module-card-list">
                        <button
                            v-for="tour in virtualTours"
                            :key="tour.id"
                            type="button"
                            :class="{ active: selectedVirtualTour?.id === tour.id }"
                            @click="selectVirtualTour(tour)"
                        >
                            <img v-if="tour.cover_image_url" :src="tour.cover_image_url" alt="" />
                            <span v-else class="module-cover-fallback">360</span>
                            <span class="module-card-body">
                                <strong>{{ tour.title }}</strong>
                                <span>{{ tour.description }}</span>
                            </span>
                        </button>
                        <p v-if="!isContentToolsLoading && virtualTours.length === 0">360° туры пока не добавлены.</p>
                    </div>
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

            <section
                class="reader-panel"
                :class="{
                    'reader-menu-open': isReaderMenuOpen,
                    'content-mode': mainContentMode !== 'chapter',
                }"
            >
                <div v-if="mainContentMode === 'chapter'" class="mini-reader-bar">
                    <span>{{ currentTitle }}</span>
                    <select
                        v-model.number="selectedChapterNumber"
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
                    <button
                        type="button"
                        aria-label="Меню чтения"
                        title="Меню чтения"
                        @click="isReaderMenuOpen = !isReaderMenuOpen"
                    >
                        <svg aria-hidden="true" viewBox="0 0 24 24">
                            <path
                                v-for="path in iconPaths(isReaderMenuOpen ? 'close' : 'menu')"
                                :key="path"
                                :d="path"
                            />
                        </svg>
                    </button>
                </div>
                <button
                    v-if="mainContentMode === 'chapter' && isReaderMenuOpen"
                    type="button"
                    class="reader-menu-backdrop"
                    aria-label="Закрыть меню чтения"
                    @click="closeReaderMenu"
                ></button>
                <div v-if="mainContentMode === 'chapter'" class="reader-toolbar">
                    <button type="button" class="bookmark" aria-label="Добавить закладку" title="Добавить закладку" @click="addBookmark">
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
                            v-for="tool in leftTools"
                            :key="`menu-${tool.id}`"
                            type="button"
                            class="reader-menu-tool"
                            :aria-label="tool.title"
                            :data-tooltip="tool.title"
                            :class="[
                                `tool-group-${tool.group}`,
                                { active: activeLeftPanel === tool.id },
                            ]"
                            @click="handleToolClick(tool.id); closeReaderMenu()"
                        >
                            <img :src="toolIconUrl(tool.icon)" :alt="tool.title" />
                        </button>
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
                            title="Показать номера Strong"
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
                        <button type="button" aria-label="Закрыть справочник" title="Закрыть справочник" @click="isStudyPanelOpen = false">
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

                <article v-if="mainContentMode === 'prayer' && selectedPrayer" class="content-reader prayer-reader">
                    <header>
                        <span>{{ prayerCategoryLabel(selectedPrayer.category) }}</span>
                        <h2>{{ selectedPrayer.title }}</h2>
                    </header>
                    <div v-if="selectedPrayer.sections && selectedPrayer.sections.length > 1" class="section-tabs">
                        <button
                            v-for="section in selectedPrayer.sections"
                            :key="section.id"
                            type="button"
                            :class="{ active: selectedPrayerSection?.id === section.id }"
                            @click="selectPrayerSection(section)"
                        >
                            {{ section.title || `Часть ${section.sort_order}` }}
                        </button>
                    </div>
                    <p v-if="isPrayerLoading || isPrayerSectionLoading" class="reader-status">Загружаю текст...</p>
                    <div v-else-if="selectedPrayerSection?.body" class="content-body" v-html="selectedPrayerSection.body"></div>
                    <div v-else class="content-body" v-html="selectedPrayer.body"></div>
                    <a v-if="selectedPrayer.source_url" :href="selectedPrayer.source_url" target="_blank" rel="noreferrer">Источник</a>
                </article>

                <article v-else-if="mainContentMode === 'recipe' && selectedRecipe" class="content-reader recipe-reader">
                    <header>
                        <span>{{ selectedRecipe.category.name }}</span>
                        <h2>{{ selectedRecipe.title }}</h2>
                    </header>
                    <img v-if="selectedRecipe.cover_image_url" class="content-cover" :src="selectedRecipe.cover_image_url" alt="" />
                    <p v-if="selectedRecipe.summary">{{ selectedRecipe.summary }}</p>
                    <section class="servings-panel">
                        <div>
                            <strong>Расчет ингредиентов</strong>
                            <small>Базовый рецепт: {{ selectedRecipeBaseServings }} порц.</small>
                        </div>
                        <label class="servings-control">
                            <span>Нужно порций</span>
                            <input v-model.number="selectedRecipeServings" type="number" min="1" max="99" />
                        </label>
                    </section>
                    <ul v-if="selectedRecipe.ingredient_items?.length" class="recipe-ingredient-list">
                        <li v-for="ingredient in selectedRecipe.ingredient_items" :key="`${ingredient.name}-${ingredient.unit}`">
                            {{ recipeIngredientLine(ingredient) }}
                        </li>
                    </ul>
                    <pre v-else-if="selectedRecipe.ingredients" class="recipe-ingredients">{{ selectedRecipe.ingredients }}</pre>
                    <section v-if="selectedRecipe.steps?.length" class="recipe-steps">
                        <article v-for="step in selectedRecipe.steps" :key="step.step_number">
                            <img v-if="step.image_url" :src="step.image_url" alt="" />
                            <strong>Шаг {{ step.step_number }}</strong>
                            <p>{{ step.body }}</p>
                        </article>
                    </section>
                    <iframe
                        v-if="selectedRecipe.youtube_url"
                        class="recipe-video"
                        :src="selectedRecipe.youtube_url"
                        title="Видео рецепта"
                        loading="lazy"
                    ></iframe>
                </article>

                <article v-else-if="mainContentMode === 'faith-question' && selectedFaithQuestion" class="content-reader faith-reader">
                    <header>
                        <span>{{ selectedFaithQuestion.category }}</span>
                        <h2>{{ selectedFaithQuestion.question }}</h2>
                    </header>
                    <div class="content-body" v-html="selectedFaithQuestion.answer_html"></div>
                    <a v-if="selectedFaithQuestion.source_url" :href="selectedFaithQuestion.source_url" target="_blank" rel="noreferrer">Источник</a>
                </article>

                <article v-else-if="mainContentMode === 'quiz' && selectedQuiz" class="content-reader quiz-reader">
                    <header>
                        <span>Тест</span>
                        <h2>{{ selectedQuiz.title }}</h2>
                    </header>
                    <p v-if="selectedQuiz.description">{{ selectedQuiz.description }}</p>
                    <section v-if="selectedQuizQuestion" class="quiz-play">
                        <div class="quiz-progress">
                            <span>Вопрос {{ selectedQuizQuestionIndex + 1 }} из {{ selectedQuizQuestions.length }}</span>
                            <span>Отвечено: {{ quizAnsweredCount }}</span>
                        </div>
                        <article class="quiz-question-card">
                            <small>{{ quizQuestionTypeLabel(selectedQuizQuestion) }}</small>
                            <strong>{{ selectedQuizQuestion.question }}</strong>
                            <img v-if="selectedQuizQuestion.image_url" class="quiz-question-image" :src="selectedQuizQuestion.image_url" alt="" />
                            <div v-if="selectedQuizQuestion.answer_type === 'text'" class="quiz-text-answer">
                                <textarea
                                    :value="quizTextAnswer(selectedQuizQuestion)"
                                    :disabled="quizSubmitted"
                                    placeholder="Напишите ответ"
                                    @input="updateQuizTextAnswer(selectedQuizQuestion, $event)"
                                ></textarea>
                            </div>
                            <div v-else class="quiz-answer-list">
                                <button
                                    v-for="answer in selectedQuizQuestion.answers"
                                    :key="answer.id"
                                    type="button"
                                    :class="quizAnswerClass(selectedQuizQuestion, answer)"
                                    :disabled="quizSubmitted"
                                    @click="toggleQuizAnswer(selectedQuizQuestion, answer)"
                                >
                                    <span aria-hidden="true"></span>
                                    <span>{{ answer.answer }}</span>
                                </button>
                            </div>
                            <div v-if="quizSubmitted" class="quiz-feedback">
                                <strong v-if="selectedQuizQuestion.answer_type !== 'text'">
                                    {{ isQuizQuestionCorrect(selectedQuizQuestion) ? 'Верно' : 'Нужно повторить' }}
                                </strong>
                                <p v-if="selectedQuizQuestion.explanation">{{ selectedQuizQuestion.explanation }}</p>
                                <p v-if="selectedQuizQuestion.recommendation?.text">{{ selectedQuizQuestion.recommendation.text }}</p>
                                <p v-if="selectedQuizQuestion.recommendation?.passage_ref">Прочесть: {{ selectedQuizQuestion.recommendation.passage_ref }}</p>
                                <template v-for="answer in selectedQuizQuestion.answers" :key="`rec-${answer.id}`">
                                    <p v-if="isQuizAnswerSelected(selectedQuizQuestion, answer) && answer.recommendation?.text">{{ answer.recommendation.text }}</p>
                                    <p v-if="isQuizAnswerSelected(selectedQuizQuestion, answer) && answer.recommendation?.passage_ref">Прочесть: {{ answer.recommendation.passage_ref }}</p>
                                </template>
                            </div>
                        </article>
                        <div class="quiz-actions">
                            <button type="button" :disabled="selectedQuizQuestionIndex === 0" @click="goToQuizQuestion(selectedQuizQuestionIndex - 1)">Назад</button>
                            <button
                                v-if="selectedQuizQuestionIndex < selectedQuizQuestions.length - 1"
                                type="button"
                                @click="goToQuizQuestion(selectedQuizQuestionIndex + 1)"
                            >
                                Далее
                            </button>
                            <button v-else-if="!quizSubmitted" type="button" class="primary" @click="submitQuiz">Проверить</button>
                            <button v-else type="button" class="primary" @click="resetQuiz">Пройти ещё раз</button>
                        </div>
                        <div v-if="quizSubmitted" class="quiz-result">
                            Результат: {{ quizCorrectAnswerCount }} из {{ quizScorableQuestions.length }}
                        </div>
                    </section>
                    <p v-else class="reader-status">В этом тесте пока нет вопросов.</p>
                </article>

                <article v-else-if="mainContentMode === 'tour' && selectedVirtualTour" class="content-reader tour-reader">
                    <header>
                        <span>360° тур</span>
                        <h2>{{ selectedVirtualTour.title }}</h2>
                    </header>
                    <p v-if="selectedVirtualTour.description">{{ selectedVirtualTour.description }}</p>
                    <iframe
                        class="tour-frame"
                        :src="selectedVirtualTour.tour_url"
                        :title="selectedVirtualTour.title"
                        loading="lazy"
                        allowfullscreen
                    ></iframe>
                </article>

                <article v-else class="chapter">
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
                                    @click.stop="selectInlineStrongNumber(verse, part.strongNumber, part.strongToken, $event)"
                                    @mouseenter="showStrongTooltipForPointer($event, verse, part.strongNumber)"
                                    @mouseleave="hideStrongTooltipForPointer"
                                    @focus="showStrongTooltipForPointer($event, verse, part.strongNumber)"
                                    @blur="hideStrongTooltipForPointer"
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

                <nav
                    v-if="mainContentMode === 'chapter' && selectedVerse && !isStudyPanelOpen && !isReaderMenuOpen"
                    class="verse-study-actions"
                    aria-label="Инструменты выбранного стиха"
                >
                    <button
                        v-for="tool in studyTools"
                        :key="`verse-study-${tool.id}`"
                        type="button"
                        :aria-label="tool.title"
                        :data-tooltip="tool.title"
                        :class="{ active: tool.id === 'strong' && showStrongNumbers }"
                        @click="openVerseStudyTool(tool.id)"
                    >
                        <img :src="toolIconUrl(tool.icon)" alt="" />
                        <span>{{ tool.id === 'references' ? 'Ссылки' : tool.id === 'notes' ? 'Заметка' : tool.id === 'feed' ? 'Лента' : 'Strong' }}</span>
                        <small v-if="tool.id === 'references' && crossReferences.length > 0">{{ crossReferences.length }}</small>
                    </button>
                </nav>

                <button
                    v-if="mainContentMode === 'chapter'"
                    type="button"
                    class="reader-bottom-menu-button"
                    aria-label="Меню чтения"
                    title="Меню чтения"
                    @click="isReaderMenuOpen = !isReaderMenuOpen"
                >
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <path
                            v-for="path in iconPaths(isReaderMenuOpen ? 'close' : 'menu')"
                            :key="path"
                            :d="path"
                        />
                    </svg>
                </button>

                <div v-if="mainContentMode === 'chapter'" class="reader-footer">
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

            <aside
                v-if="mainContentMode === 'chapter'"
                class="analysis-panel"
                :class="{
                    'is-open': isStudyPanelOpen,
                    'is-reference-sheet': activeStudyTab === 'references',
                }"
            >
                <header>
                    <h2>Справочник</h2>
                    <button type="button" class="analysis-close" aria-label="Закрыть справочник" title="Закрыть справочник" @click="isStudyPanelOpen = false">
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
                    <button
                        v-for="tool in studyTools"
                        :key="`study-${tool.id}`"
                        type="button"
                        :title="tool.title"
                        :class="{ active: activeStudyTab === tool.id }"
                        @click="openStudyTool(tool.id)"
                    >
                        <img :src="toolIconUrl(tool.icon)" :alt="tool.title" />
                        <span class="sr-only">{{ tool.title }}</span>
                    </button>
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
                    <p class="note-private-hint">Заметки видите только вы. Это личный комментарий к выбранному стиху.</p>
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
                    <button
                        type="submit"
                        class="note-submit"
                        :disabled="!selectedVerse?.id || noteBody.trim().length === 0 || isNotesLoading"
                    >
                        Написать
                    </button>
                </form>

                <section v-if="activeStudyTab === 'feed'" class="social-feed">
                    <div v-if="!currentUser" class="guest-warning">
                        Лента доступна после входа.
                        <a :href="appConfig.auth.login_url">Войти</a>
                        <a :href="appConfig.auth.register_url">Регистрация</a>
                    </div>
                    <button
                        v-else
                        type="button"
                        class="feed-compose-open"
                        @click="openSocialComposer"
                    >
                        <svg aria-hidden="true" viewBox="0 0 24 24">
                            <path
                                v-for="path in iconPaths('plus')"
                                :key="path"
                                :d="path"
                            />
                        </svg>
                        <span>Написать в ленту</span>
                    </button>
                    <div v-if="currentUser" class="feed-scope">
                        <button
                            type="button"
                            :class="{ active: socialFeedScope === 'book' }"
                            @click="socialFeedScope = 'book'"
                        >
                            {{ currentBook?.name ?? 'Текущая книга' }}
                        </button>
                        <button
                            type="button"
                            :class="{ active: socialFeedScope === 'all' }"
                            @click="socialFeedScope = 'all'"
                        >
                            Вся лента
                        </button>
                    </div>
                    <p v-if="currentUser" class="feed-note">
                        Публикации “для подписчиков” видят только ваши подписчики. Свои публикации видны вам всегда.
                    </p>
                    <p v-if="isSocialFeedLoading">Загружаю ленту...</p>
                    <p v-else-if="socialFeedError">API: {{ socialFeedError }}</p>
                    <button
                        v-for="post in socialPosts"
                        :key="post.id"
                        type="button"
                        class="feed-post"
                        :disabled="!post.book_slug"
                        @click="openSocialPost(post)"
                    >
                        <strong>{{ post.author }}</strong>
                        <small v-if="post.reference">{{ post.reference }}</small>
                        <p>{{ post.body }}</p>
                    </button>
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

        <section
            v-if="isSocialComposerOpen"
            class="social-composer-overlay"
            role="dialog"
            aria-modal="true"
            aria-label="Новая публикация в ленту"
            @click.self="closeSocialComposer"
        >
            <form class="social-composer-dialog" @submit.prevent="submitSocialPost">
                <header>
                    <div>
                        <span>Лента размышлений</span>
                        <h2>Новая публикация</h2>
                        <p v-if="selectedVerse">{{ verseReference(selectedVerse) }}</p>
                    </div>
                    <button
                        type="button"
                        class="social-composer-close"
                        aria-label="Закрыть редактор"
                        @click="closeSocialComposer"
                    >
                        <svg aria-hidden="true" viewBox="0 0 24 24">
                            <path
                                v-for="path in iconPaths('close')"
                                :key="path"
                                :d="path"
                            />
                        </svg>
                    </button>
                </header>
                <textarea
                    v-model="socialPostBody"
                    autofocus
                    placeholder="Напишите публикацию"
                ></textarea>
                <footer>
                    <button type="button" class="secondary" @click="closeSocialComposer">Отмена</button>
                    <button
                        type="submit"
                        class="primary"
                        :disabled="socialPostBody.trim().length === 0 || isSocialFeedLoading"
                    >
                        Опубликовать
                    </button>
                </footer>
            </form>
        </section>

        <section
            v-if="isVirtualTourOverlayOpen && selectedVirtualTour"
            class="tour-overlay"
            role="dialog"
            aria-modal="true"
            :aria-label="selectedVirtualTour.title"
            @click.self="closeVirtualTourOverlay"
        >
            <article class="tour-dialog">
                <header>
                    <div>
                        <span>360° тур</span>
                        <h2>{{ selectedVirtualTour.title }}</h2>
                    </div>
                    <button
                        type="button"
                        aria-label="Закрыть 360° тур"
                        title="Закрыть"
                        @click="closeVirtualTourOverlay"
                    >
                        <svg aria-hidden="true" viewBox="0 0 24 24">
                            <path
                                v-for="path in iconPaths('close')"
                                :key="path"
                                :d="path"
                            />
                        </svg>
                    </button>
                </header>
                <p v-if="selectedVirtualTour.description">{{ selectedVirtualTour.description }}</p>
                <iframe
                    class="tour-dialog-frame"
                    :src="selectedVirtualTour.tour_url"
                    :title="selectedVirtualTour.title"
                    loading="lazy"
                    allowfullscreen
                ></iframe>
            </article>
        </section>

        <footer class="footerbar">
            <button type="button">{{ selectedLanguage }}</button>
            <div class="footer-credit">
                <span>© 2026 Bible Desktop</span>
                <span>
                    Powered by:
                    <a href="https://bible-media.de/" target="_blank" rel="noreferrer">Bible Media Agentur</a>
                </span>
                <a href="https://georg-kloster.ru/" target="_blank" rel="noreferrer">Проект для Свято-Георгиевского монастыря</a>
            </div>
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
        <h1>{{ printDocument.title }}</h1>
        <p v-if="printDocument.subtitle" class="print-subtitle">{{ printDocument.subtitle }}</p>
        <article
            v-for="(block, index) in printDocument.blocks"
            :key="`print-block-${index}`"
            class="print-block"
            :class="{ 'print-verse': block.kind === 'verse' }"
        >
            <h2>{{ block.title }}</h2>
            <img v-if="block.image" :src="block.image" alt="" />
            <div v-if="block.html" v-html="block.html"></div>
            <p v-else>{{ block.body }}</p>
        </article>
        <footer>https://bible-desktop.com</footer>
    </section>
</template>
