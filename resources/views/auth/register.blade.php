<x-public-layout title="Регистрация - Bible Desktop">
    <section class="auth-card">
        <h1>Регистрация</h1>
        <p>Аккаунт нужен, чтобы ваши заметки, закладки, история и публикации не зависели от браузера.</p>

        <form method="post" action="{{ route('register.store') }}">
            @csrf
            <label>
                Имя
                <input name="name" type="text" value="{{ old('name') }}" required autofocus>
            </label>
            <label>
                Email
                <input name="email" type="email" value="{{ old('email') }}" required>
            </label>
            <label>
                Пароль
                <input name="password" type="password" required>
            </label>
            <label>
                Повторите пароль
                <input name="password_confirmation" type="password" required>
            </label>
            @if ($errors->any())
                <div class="auth-errors">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            <button type="submit">Создать аккаунт</button>
        </form>

        <a href="{{ route('login') }}">Уже есть аккаунт</a>
    </section>
</x-public-layout>
