<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';

type LanguageDto = {
    code: string;
    name: string;
    native_name: string;
};

type CanonBookDto = {
    slug: string;
    osis_code: string;
    testament: 'old' | 'new' | 'apocrypha';
    order: number;
    chapters_count: number;
    is_deuterocanonical: boolean;
    names: Record<string, { name: string; short_name: string; aliases: string[] }>;
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

type Tab = {
    title: string;
    locked?: boolean;
    active?: boolean;
};

const languages = ref<LanguageDto[]>([]);
const translations = ref<TranslationDto[]>([]);
const books = ref<CanonBookDto[]>([]);
const isLoading = ref(true);
const isChapterLoading = ref(false);
const apiError = ref<string | null>(null);
const selectedTranslationCode = ref('L1_RST');
const selectedBookSlug = ref('genesis');
const selectedChapterNumber = ref(1);

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
const visibleBooks = computed(() => books.value.slice(0, 12));
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
    const bookName = currentBook.value?.names.en?.name ?? currentBook.value?.slug ?? 'Библия';

    return `${bookName} ${selectedChapterNumber.value}`;
});

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
        apiError.value = null;
    } catch (error) {
        currentVerses.value = demoVerses;
        apiError.value = error instanceof Error ? error.message : 'Не удалось загрузить главу';
    } finally {
        isChapterLoading.value = false;
    }
}

function changeBook(): void {
    selectedChapterNumber.value = 1;
    void loadChapter();
}

function chooseBook(slug: string): void {
    selectedBookSlug.value = slug;
    changeBook();
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

onMounted(async () => {
    try {
        const [languagesResponse, translationsResponse, booksResponse] = await Promise.all([
            loadJson<ApiResponse<LanguageDto[]>>('/api/languages'),
            loadJson<ApiResponse<TranslationDto[]>>('/api/translations'),
            loadJson<ApiResponse<{ books: CanonBookDto[] }>>('/api/canons/orthodox/books'),
        ]);

        languages.value = languagesResponse.data;
        translations.value = translationsResponse.data;
        books.value = booksResponse.data.books;
        selectedTranslationCode.value = translations.value.find((translation) => translation.is_default)?.code
            ?? translations.value.find((translation) => translation.code === 'L1_RST')?.code
            ?? translations.value[0]?.code
            ?? 'L1_RST';
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
                        @change="loadChapter"
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
                        <template v-if="isLoading">
                            <option>Загрузка книг...</option>
                        </template>
                        <template v-else>
                            <option
                                v-for="book in books"
                                :key="book.slug"
                                :value="book.slug"
                            >
                                {{ book.names.en?.name ?? book.slug }} ({{ book.chapters_count }})
                            </option>
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
                    <p v-for="verse in currentVerses" :key="verse.number">
                        <button type="button" class="verse-number">{{ verse.number }}</button>
                        <span>{{ verse.text }}</span>
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
                    <button type="button" class="active">Канон</button>
                    <button type="button">Заметки</button>
                </div>

                <section class="note">
                    <div class="note-meta">
                        <div class="avatar small">{{ selectedLanguage.slice(0, 2).toUpperCase() }}</div>
                        <strong>Orthodox canon</strong>
                        <span>{{ books.length || 0 }} книг</span>
                    </div>
                    <p v-if="isLoading">Загружаю языки и канон из API.</p>
                    <p v-else-if="apiError">API: {{ apiError }}</p>
                    <p v-else>
                        Доступно языков: {{ languages.length }}. Канон готов к привязке переводов.
                    </p>
                </section>

                <section class="events">
                    <h3>Первые книги</h3>
                    <div class="book-list">
                        <button
                            v-for="book in visibleBooks"
                            :key="book.slug"
                            type="button"
                            :class="{ active: book.slug === currentBook?.slug }"
                            @click="chooseBook(book.slug)"
                        >
                            <span>{{ book.order }}</span>
                            {{ book.names.en?.name ?? book.slug }}
                        </button>
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
