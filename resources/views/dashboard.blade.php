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

        <div class="dashboard-grid">
            <article>
                <h2>Заметки</h2>
                @forelse ($notes as $note)
                    <form method="post" action="{{ route('dashboard.notes.update', $note->id) }}" class="dashboard-item-form">
                        @csrf
                        @method('PUT')
                        <strong>{{ $note->osis_code }} {{ $note->chapter_number }}:{{ $note->verse_number }}</strong>
                        <textarea name="body" rows="3">{{ $note->body }}</textarea>
                        <div>
                            <button type="submit">Сохранить</button>
                            <button
                                type="submit"
                                form="delete-note-{{ $note->id }}"
                                class="danger"
                                onclick="return confirm('Удалить заметку?')"
                            >
                                Удалить
                            </button>
                        </div>
                    </form>
                    <form id="delete-note-{{ $note->id }}" method="post" action="{{ route('dashboard.notes.delete', $note->id) }}" hidden>
                        @csrf
                        @method('DELETE')
                    </form>
                @empty
                    <p>Заметок пока нет.</p>
                @endforelse
            </article>

            <article>
                <h2>Закладки</h2>
                @forelse ($bookmarks as $bookmark)
                    <form method="post" action="{{ route('dashboard.bookmarks.update', $bookmark->id) }}" class="dashboard-item-form">
                        @csrf
                        @method('PUT')
                        <strong>{{ $bookmark->title ?: trim(($bookmark->osis_code ?? '').' '.($bookmark->chapter_number ?? '').':'.($bookmark->verse_number ?? ''), ' :') }}</strong>
                        <input name="title" value="{{ $bookmark->title }}" placeholder="Название">
                        <textarea name="description" rows="2" placeholder="Описание">{{ $bookmark->description }}</textarea>
                        <div>
                            <button type="submit">Сохранить</button>
                            <button
                                type="submit"
                                form="delete-bookmark-{{ $bookmark->id }}"
                                class="danger"
                                onclick="return confirm('Удалить закладку?')"
                            >
                                Удалить
                            </button>
                        </div>
                    </form>
                    <form id="delete-bookmark-{{ $bookmark->id }}" method="post" action="{{ route('dashboard.bookmarks.delete', $bookmark->id) }}" hidden>
                        @csrf
                        @method('DELETE')
                    </form>
                @empty
                    <p>Закладок пока нет.</p>
                @endforelse
            </article>

            <article>
                <h2>Публикации</h2>
                @forelse ($posts as $post)
                    <form method="post" action="{{ route('dashboard.posts.update', $post->id) }}" class="dashboard-item-form">
                        @csrf
                        @method('PUT')
                        <select name="visibility">
                            <option value="private" @selected($post->visibility === 'private')>Только я</option>
                            <option value="followers" @selected($post->visibility === 'followers')>Подписчики</option>
                            <option value="friends" @selected($post->visibility === 'friends')>Друзья</option>
                            <option value="public" @selected($post->visibility === 'public')>Все</option>
                        </select>
                        <textarea name="body" rows="3">{{ $post->body }}</textarea>
                        <div>
                            <button type="submit">Сохранить</button>
                            <button
                                type="submit"
                                form="delete-post-{{ $post->id }}"
                                class="danger"
                                onclick="return confirm('Удалить публикацию?')"
                            >
                                Удалить
                            </button>
                        </div>
                    </form>
                    <form id="delete-post-{{ $post->id }}" method="post" action="{{ route('dashboard.posts.delete', $post->id) }}" hidden>
                        @csrf
                        @method('DELETE')
                    </form>
                @empty
                    <p>Публикаций пока нет.</p>
                @endforelse
            </article>
        </div>
    </section>
</x-public-layout>
