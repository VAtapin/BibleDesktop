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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faith_questions', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 160)->unique();
            $table->string('category', 120)->default('Основы');
            $table->string('question', 300);
            $table->text('answer_html');
            $table->string('source_url', 500)->nullable();
            $table->integer('sort_order')->default(0)->index();
            $table->boolean('is_public')->default(true)->index();
            $table->timestamps();
        });

        $now = now();
        DB::table('faith_questions')->insert([
            [
                'slug' => 'chto-takoe-molitva',
                'category' => 'Молитва',
                'question' => 'Что такое молитва?',
                'answer_html' => '<p>Молитва - это обращение ума и сердца к Богу. В молитве человек беседует с Богом, благодарит, просит, кается и славословит.</p>',
                'source_url' => 'https://azbyka.ru/molitvoslov/',
                'sort_order' => 10,
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'zachem-nuzhna-molitva',
                'category' => 'Молитва',
                'question' => 'Зачем нужна молитва?',
                'answer_html' => '<p>Молитва соединяет человека с Богом, помогает жить внимательнее, бороться со страстями и искать Божию волю, а не только земную пользу.</p>',
                'source_url' => 'https://azbyka.ru/molitvoslov/',
                'sort_order' => 20,
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'kak-nado-molitsya',
                'category' => 'Молитва',
                'question' => 'Как надо молиться?',
                'answer_html' => '<p>Молиться нужно искренне, со смирением, вниманием и примирением с ближними. Важна не красота слов, а сердце, обращенное к Богу.</p>',
                'source_url' => 'https://azbyka.ru/molitvoslov/',
                'sort_order' => 30,
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'mozhno-li-molitsya-svoimi-slovami',
                'category' => 'Молитва',
                'question' => 'Можно ли молиться своими словами?',
                'answer_html' => '<p>Можно и нужно. Молитвословия Церкви не отменяют личной молитвы, а помогают ей стать глубже и внимательнее.</p>',
                'source_url' => 'https://azbyka.ru/molitvoslov/',
                'sort_order' => 40,
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'chem-molitva-otlichaetsya-ot-zagovora',
                'category' => 'Молитва',
                'question' => 'Чем молитва отличается от заговора?',
                'answer_html' => '<p>Молитва обращена к Богу и совершается с доверием Его воле. Заговор пытается принудить духовный мир и чужд христианской вере.</p>',
                'source_url' => 'https://azbyka.ru/molitvoslov/',
                'sort_order' => 50,
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('faith_questions');
    }
};
