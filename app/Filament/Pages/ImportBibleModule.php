<?php

namespace App\Filament\Pages;

use App\Support\ModuleImportInspector;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class ImportBibleModule extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowUpTray;

    protected static string|UnitEnum|null $navigationGroup = 'Bible';

    protected static ?string $navigationLabel = 'Import module';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.import-bible-module';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * @var array<string, mixed>|null
     */
    public ?array $report = null;

    public ?string $importOutput = null;

    public function mount(): void
    {
        $this->form->fill([
            'languages' => ['ru', 'de', 'en', 'uk'],
            'replace' => false,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('module_file')
                    ->label('ZIP или SQLite3 модуль')
                    ->disk('local')
                    ->directory('module-imports')
                    ->acceptedFileTypes([
                        'application/zip',
                        'application/x-zip-compressed',
                        'application/octet-stream',
                        'application/vnd.sqlite3',
                    ])
                    ->required(),
                Select::make('languages')
                    ->label('Разрешённые языки')
                    ->multiple()
                    ->options([
                        'ru' => 'Русский',
                        'de' => 'Немецкий',
                        'en' => 'Английский',
                        'uk' => 'Украинский',
                    ])
                    ->default(['ru', 'de', 'en', 'uk'])
                    ->required(),
                Toggle::make('replace')
                    ->label('Перед импортом удалить все текущие Bible-модули'),
            ])
            ->statePath('data');
    }

    public function inspect(): void
    {
        $state = $this->form->getState();
        $path = $this->uploadedPath($state);
        $languages = array_values((array) ($state['languages'] ?? ['ru', 'de', 'en', 'uk']));

        $this->report = app(ModuleImportInspector::class)->inspect($path, $languages);
        $this->importOutput = null;

        $notification = Notification::make()
            ->title($this->report['importable'] ? 'Модуль можно импортировать' : 'Модуль требует внимания')
            ->body($this->report['importable'] ? 'Проверка завершена без блокирующих ошибок.' : implode("\n", $this->report['errors'] ?? []));

        ($this->report['importable'] ? $notification->success() : $notification->warning())->send();
    }

    public function import(): void
    {
        $state = $this->form->getState();
        $path = $this->uploadedPath($state);
        $languages = implode(',', array_values((array) ($state['languages'] ?? ['ru', 'de', 'en', 'uk'])));
        $this->report = app(ModuleImportInspector::class)->inspect($path, explode(',', $languages));

        if (! ($this->report['importable'] ?? false)) {
            Notification::make()
                ->title('Импорт остановлен')
                ->body(implode("\n", $this->report['errors'] ?? ['Сначала исправьте ошибки проверки.']))
                ->danger()
                ->send();

            return;
        }

        $params = [
            '--path' => $path,
            '--languages' => $languages,
        ];

        if ((bool) ($state['replace'] ?? false)) {
            $params['--replace'] = true;
        }

        $exitCode = Artisan::call('bible:bq:import', $params);
        $this->importOutput = trim(Artisan::output());

        $notification = Notification::make()
            ->title($exitCode === 0 ? 'Импорт завершён' : 'Импорт завершился с ошибкой')
            ->body($this->importOutput ?: null);

        ($exitCode === 0 ? $notification->success() : $notification->danger())->send();
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function uploadedPath(array $state): string
    {
        $file = $state['module_file'] ?? null;

        if (is_array($file)) {
            $file = reset($file);
        }

        return Storage::disk('local')->path((string) $file);
    }
}
