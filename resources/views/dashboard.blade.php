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

        <div class="dashboard-grid">
            <article>
                <h2>Заметки</h2>
                @forelse ($notes as $note)
                    <p>
                        <strong>{{ $note->osis_code }} {{ $note->chapter_number }}:{{ $note->verse_number }}</strong>
                        <span>{{ $note->body }}</span>
                    </p>
                @empty
                    <p>Заметок пока нет.</p>
                @endforelse
            </article>

            <article>
                <h2>Закладки</h2>
                @forelse ($bookmarks as $bookmark)
                    <p>
                        <strong>{{ $bookmark->title ?: trim(($bookmark->osis_code ?? '').' '.($bookmark->chapter_number ?? '').':'.($bookmark->verse_number ?? ''), ' :') }}</strong>
                        <span>{{ $bookmark->description }}</span>
                    </p>
                @empty
                    <p>Закладок пока нет.</p>
                @endforelse
            </article>

            <article>
                <h2>Публикации</h2>
                @forelse ($posts as $post)
                    <p>
                        <strong>{{ $post->visibility }}</strong>
                        <span>{{ $post->body }}</span>
                    </p>
                @empty
                    <p>Публикаций пока нет.</p>
                @endforelse
            </article>
        </div>
    </section>
</x-public-layout>
