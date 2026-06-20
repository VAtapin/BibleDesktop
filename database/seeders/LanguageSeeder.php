<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('languages')->upsert([
            [
                'code' => 'ru',
                'name' => 'Russian',
                'native_name' => 'Русский',
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'uk',
                'name' => 'Ukrainian',
                'native_name' => 'Українська',
                'is_active' => true,
                'sort_order' => 20,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'be',
                'name' => 'Belarusian',
                'native_name' => 'Беларуская',
                'is_active' => true,
                'sort_order' => 30,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'de',
                'name' => 'German',
                'native_name' => 'Deutsch',
                'is_active' => true,
                'sort_order' => 40,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'is_active' => true,
                'sort_order' => 50,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['code' => 'fr', 'name' => 'French', 'native_name' => 'Francais', 'is_active' => true, 'sort_order' => 60, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'es', 'name' => 'Spanish', 'native_name' => 'Espanol', 'is_active' => true, 'sort_order' => 70, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'et', 'name' => 'Estonian', 'native_name' => 'Eesti', 'is_active' => true, 'sort_order' => 80, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'hy', 'name' => 'Armenian', 'native_name' => 'Հայերեն', 'is_active' => true, 'sort_order' => 90, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'az', 'name' => 'Azerbaijani', 'native_name' => 'Azərbaycanca', 'is_active' => true, 'sort_order' => 100, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'el', 'name' => 'Greek', 'native_name' => 'Ελληνικά', 'is_active' => true, 'sort_order' => 110, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'he', 'name' => 'Hebrew', 'native_name' => 'עברית', 'is_active' => true, 'sort_order' => 120, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'kk', 'name' => 'Kazakh', 'native_name' => 'Қазақша', 'is_active' => true, 'sort_order' => 130, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'la', 'name' => 'Latin', 'native_name' => 'Latina', 'is_active' => true, 'sort_order' => 140, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'lv', 'name' => 'Latvian', 'native_name' => 'Latviesu', 'is_active' => true, 'sort_order' => 150, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'lt', 'name' => 'Lithuanian', 'native_name' => 'Lietuviu', 'is_active' => true, 'sort_order' => 160, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'mk', 'name' => 'Macedonian', 'native_name' => 'Македонски', 'is_active' => true, 'sort_order' => 170, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'pl', 'name' => 'Polish', 'native_name' => 'Polski', 'is_active' => true, 'sort_order' => 180, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'sr', 'name' => 'Serbian', 'native_name' => 'Српски', 'is_active' => true, 'sort_order' => 190, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'hr', 'name' => 'Croatian', 'native_name' => 'Hrvatski', 'is_active' => true, 'sort_order' => 200, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'sk', 'name' => 'Slovak', 'native_name' => 'Slovencina', 'is_active' => true, 'sort_order' => 210, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'sl', 'name' => 'Slovenian', 'native_name' => 'Slovenscina', 'is_active' => true, 'sort_order' => 220, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'fi', 'name' => 'Finnish', 'native_name' => 'Suomi', 'is_active' => true, 'sort_order' => 230, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'sv', 'name' => 'Swedish', 'native_name' => 'Svenska', 'is_active' => true, 'sort_order' => 240, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'tr', 'name' => 'Turkish', 'native_name' => 'Turkce', 'is_active' => true, 'sort_order' => 250, 'created_at' => $now, 'updated_at' => $now],
            ['code' => 'uz', 'name' => 'Uzbek', 'native_name' => 'Oʻzbekcha', 'is_active' => true, 'sort_order' => 260, 'created_at' => $now, 'updated_at' => $now],
        ], ['code'], ['name', 'native_name', 'is_active', 'sort_order', 'updated_at']);
    }
}
