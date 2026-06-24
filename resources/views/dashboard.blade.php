<x-public-layout title="Личный кабинет - Bible Desktop">
    <section class="dashboard-card">
        <header>
            <div>
                <h1>Личный кабинет</h1>
                <p>{{ auth()->user()->name }} · {{ auth()->user()->email }}</p>
            </div>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Выйти</button>
            </form>
        </header>

        @if (session('status'))
            <p class="dashboard-status">{{ session('status') }}</p>
        @endif

        <div class="dashboard-social-stats">
            <span>Подписчики: <strong>{{ $socialStats['followers'] }}</strong></span>
            <span>Подписки: <strong>{{ $socialStats['following'] }}</strong></span>
            <span>Друзья: <strong>{{ $socialStats['friends'] }}</strong></span>
        </div>

        <nav class="dashboard-tabs" aria-label="Разделы личного кабинета">
            <a @class(['active' => $activeSection === 'bookmarks']) href="{{ route('dashboard', ['section' => 'bookmarks']) }}">
                Закладки <span>{{ $counts['bookmarks'] }}</span>
            </a>
            <a @class(['active' => $activeSection === 'notes']) href="{{ route('dashboard', ['section' => 'notes']) }}">
                Заметки <span>{{ $counts['notes'] }}</span>
            </a>
            <a @class(['active' => $activeSection === 'posts']) href="{{ route('dashboard', ['section' => 'posts']) }}">
                Публикации <span>{{ $counts['posts'] }}</span>
            </a>
        </nav>

        @if ($activeSection === 'notes')
        <article class="dashboard-section">
            <h2>Заметки</h2>
            <div class="dashboard-table-wrap">
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>Место</th>
                            <th>Текст</th>
                            <th>Дата</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($notes as $note)
                            <tr>
                                <td>{{ $note->osis_code }} {{ $note->chapter_number }}:{{ $note->verse_number }}</td>
                                <td class="dashboard-text-cell">{{ $note->body }}</td>
                                <td>{{ \Illuminate\Support\Carbon::parse($note->created_at)->format('d.m.Y H:i') }}</td>
                                <td>
                                    <details class="dashboard-row-editor">
                                        <summary>Редактировать</summary>
                                        <form method="post" action="{{ route('dashboard.notes.update', $note->id) }}">
                                            @csrf
                                            @method('PUT')
                                            <textarea name="body" rows="4">{{ $note->body }}</textarea>
                                            <button type="submit">Сохранить</button>
                                        </form>
                                    </details>
                                    <form method="post" action="{{ route('dashboard.notes.delete', $note->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="dashboard-delete" type="submit" onclick="return confirm('Удалить заметку?')">×</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4">Заметок пока нет.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $notes->withQueryString()->links() }}
        </article>
        @endif

        @if ($activeSection === 'bookmarks')
        <article class="dashboard-section">
            <h2>Закладки</h2>
            <div class="dashboard-table-wrap">
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>Место</th>
                            <th>Название</th>
                            <th>Описание</th>
                            <th>Дата</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bookmarks as $bookmark)
                            <tr>
                                <td>{{ $bookmark->osis_code }} {{ $bookmark->chapter_number }}:{{ $bookmark->verse_number }}</td>
                                <td>{{ $bookmark->title ?: 'Закладка' }}</td>
                                <td class="dashboard-text-cell">{{ $bookmark->description }}</td>
                                <td>{{ \Illuminate\Support\Carbon::parse($bookmark->created_at)->format('d.m.Y H:i') }}</td>
                                <td>
                                    <form method="post" action="{{ route('dashboard.bookmarks.delete', $bookmark->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="dashboard-delete" type="submit" aria-label="Удалить закладку" onclick="return confirm('Удалить закладку?')">×</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5">Закладок пока нет.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $bookmarks->withQueryString()->links() }}
        </article>
        @endif

        @if ($activeSection === 'posts')
        <article class="dashboard-section">
            <h2>Публикации</h2>
            <div class="dashboard-table-wrap">
                <table class="dashboard-table">
                    <thead>
                        <tr>
                            <th>Видимость</th>
                            <th>Текст</th>
                            <th>Дата</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($posts as $post)
                            <tr>
                                <td>{{ ['private' => 'Только я', 'followers' => 'Подписчики', 'friends' => 'Друзья', 'public' => 'Все'][$post->visibility] ?? $post->visibility }}</td>
                                <td class="dashboard-text-cell">{{ $post->body }}</td>
                                <td>{{ \Illuminate\Support\Carbon::parse($post->created_at)->format('d.m.Y H:i') }}</td>
                                <td>
                                    <details class="dashboard-row-editor">
                                        <summary>Редактировать</summary>
                                        <form method="post" action="{{ route('dashboard.posts.update', $post->id) }}">
                                            @csrf
                                            @method('PUT')
                                            <select name="visibility">
                                                <option value="private" @selected($post->visibility === 'private')>Только я</option>
                                                <option value="followers" @selected($post->visibility === 'followers')>Подписчики</option>
                                                <option value="friends" @selected($post->visibility === 'friends')>Друзья</option>
                                                <option value="public" @selected($post->visibility === 'public')>Все</option>
                                            </select>
                                            <textarea name="body" rows="4">{{ $post->body }}</textarea>
                                            <button type="submit">Сохранить</button>
                                        </form>
                                    </details>
                                    <form method="post" action="{{ route('dashboard.posts.delete', $post->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="dashboard-delete" type="submit" onclick="return confirm('Удалить публикацию?')">×</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4">Публикаций пока нет.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $posts->withQueryString()->links() }}
        </article>
        @endif
    </section>
</x-public-layout>
