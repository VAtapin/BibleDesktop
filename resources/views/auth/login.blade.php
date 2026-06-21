<x-public-layout title="Вход - Bible Desktop">
    <section class="auth-card">
        <h1>Вход</h1>
        <p>Войдите, чтобы сохранять заметки, закладки и историю в личном кабинете.</p>

        <form method="post" action="{{ route('login.store') }}">
            @csrf
            <label>
                Email
                <input name="email" type="email" value="{{ old('email') }}" required autofocus>
            </label>
            <label>
                Пароль
                <input name="password" type="password" required>
            </label>
            <label class="auth-check">
                <input name="remember" type="checkbox" value="1">
                Запомнить меня
            </label>
            @if ($errors->any())
                <div class="auth-errors">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif
            <button type="submit">Войти</button>
        </form>

        <a href="{{ route('register') }}">Создать аккаунт</a>
    </section>
</x-public-layout>
