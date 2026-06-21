<?php

/**
 * BibleDesktop - Bible study desktop and web application.
 *
 * @author Atapin Vladimir <atapin@gmail.com>
 *
 * @link https://bible-desktop.com/
 *
 * @copyright 2026 Atapin Vladimir / Bible Media
 *
 * @version 1.0.0
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Rebuilds the application database and imports the initial Bible modules.
 */
class FreshImportBibleModules extends Command
{
    protected $signature = 'bible:fresh-import
        {--dir=OLD/Mod : Directory that contains module archives}
        {--module=* : File names to import; defaults to the first supported ru/de/en/uk set}
        {--languages=ru,de,en,uk : Allowed language codes for the module importer}';

    protected $description = 'Drop all tables, migrate, seed core data, and import the initial Bible module set.';

    /**
     * @var list<string>
     */
    private const DEFAULT_MODULES = [
        'Bible_Russian_RST-Strong_2019-05-30.zip',
        'Bible_German_Elberfeld_Strong.zip',
        'Bible_English_KJV-1769_2019-05-30.zip',
        'Bible_Ukraine.zip',
    ];

    public function handle(): int
    {
        $directory = base_path((string) $this->option('dir'));
        $modules = $this->option('module') ?: self::DEFAULT_MODULES;

        if (! is_dir($directory)) {
            $this->error("Module directory not found: {$directory}");

            return self::FAILURE;
        }

        $paths = array_map(
            fn (string $module): string => $directory.DIRECTORY_SEPARATOR.$module,
            array_values($modules),
        );

        foreach ($paths as $path) {
            if (! is_file($path)) {
                $this->error("Module file not found: {$path}");

                return self::FAILURE;
            }
        }

        $this->components->warn('This command drops all database tables before importing modules.');
        Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]);
        $this->output->write(Artisan::output());

        foreach ($paths as $path) {
            $this->components->info('Importing '.basename($path));
            $exitCode = Artisan::call('bible:bq:import', [
                '--path' => $path,
                '--languages' => (string) $this->option('languages'),
            ]);
            $this->output->write(Artisan::output());

            if ($exitCode !== self::SUCCESS) {
                $this->error('Import failed: '.basename($path));

                return self::FAILURE;
            }
        }

        $this->components->info('Fresh BibleDesktop database is ready.');

        return self::SUCCESS;
    }
}
