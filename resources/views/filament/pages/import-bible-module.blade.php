<x-filament-panels::page>
    <form wire:submit="inspect" class="space-y-6">
        {{ $this->form }}

        <div class="flex flex-wrap gap-3">
            <x-filament::button type="submit">
                Проверить
            </x-filament::button>

            <x-filament::button
                type="button"
                color="success"
                wire:click="import"
                :disabled="! ($report['importable'] ?? false)"
            >
                Импортировать
            </x-filament::button>
        </div>
    </form>

    @if ($report)
        <x-filament::section>
            <x-slot name="heading">
                Результат проверки
            </x-slot>

            <dl class="grid gap-3 text-sm md:grid-cols-2">
                <div>
                    <dt class="font-medium text-gray-500 dark:text-gray-400">Файл</dt>
                    <dd>{{ $report['file'] ?? '' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500 dark:text-gray-400">Формат</dt>
                    <dd>{{ $report['format'] ?? $report['type'] ?? '' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500 dark:text-gray-400">Название</dt>
                    <dd>{{ $report['name'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500 dark:text-gray-400">Язык</dt>
                    <dd>{{ $report['language'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500 dark:text-gray-400">Книги</dt>
                    <dd>{{ $report['books'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500 dark:text-gray-400">Стихи</dt>
                    <dd>{{ $report['verses'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500 dark:text-gray-400">Strong</dt>
                    <dd>{{ ($report['has_strong'] ?? false) ? 'есть' : 'нет' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500 dark:text-gray-400">Кодировка</dt>
                    <dd>{{ $report['encoding'] ?? '-' }}</dd>
                </div>
            </dl>

            @if (! empty($report['errors']))
                <div class="mt-4 rounded-lg border border-danger-200 bg-danger-50 p-4 text-sm text-danger-700 dark:border-danger-900 dark:bg-danger-950 dark:text-danger-300">
                    <strong>Ошибки:</strong>
                    <ul class="mt-2 list-disc pl-5">
                        @foreach ($report['errors'] as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (! empty($report['warnings']))
                <div class="mt-4 rounded-lg border border-warning-200 bg-warning-50 p-4 text-sm text-warning-700 dark:border-warning-900 dark:bg-warning-950 dark:text-warning-300">
                    <strong>Предупреждения:</strong>
                    <ul class="mt-2 list-disc pl-5">
                        @foreach ($report['warnings'] as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-filament::section>
    @endif

    @if ($importOutput)
        <x-filament::section>
            <x-slot name="heading">
                Вывод импорта
            </x-slot>

            <pre class="overflow-auto rounded-lg bg-gray-950 p-4 text-xs text-gray-100">{{ $importOutput }}</pre>
        </x-filament::section>
    @endif
</x-filament-panels::page>
