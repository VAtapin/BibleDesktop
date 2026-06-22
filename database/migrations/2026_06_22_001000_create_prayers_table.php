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
        Schema::create('prayers', function (Blueprint $table): void {
            $table->id();
            $table->string('language_code', 12)->default('ru');
            $table->string('category', 60)->default('common');
            $table->string('liturgy_key', 40)->nullable();
            $table->string('title', 180);
            $table->string('short_title', 80)->nullable();
            $table->longText('body');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->index(['language_code', 'category', 'liturgy_key', 'sort_order']);
            $table->index(['is_public', 'sort_order']);
        });

        $now = now();
        DB::table('prayers')->insert([
            [
                'language_code' => 'ru',
                'category' => 'common',
                'liturgy_key' => null,
                'title' => 'Отче наш',
                'short_title' => 'Отче наш',
                'body' => 'Отче наш, Иже еси на небесех! Да святится имя Твое, да приидет Царствие Твое, да будет воля Твоя, яко на небеси и на земли. Хлеб наш насущный даждь нам днесь; и остави нам долги наша, якоже и мы оставляем должником нашим; и не введи нас во искушение, но избави нас от лукаваго.',
                'sort_order' => 10,
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'language_code' => 'cu',
                'category' => 'common',
                'liturgy_key' => null,
                'title' => 'Отче наш',
                'short_title' => 'Отче наш',
                'body' => 'Отче наш, Иже еси на небесех! Да святится имя Твое, да приидет Царствие Твое, да будет воля Твоя, яко на небеси и на земли. Хлеб наш насущный даждь нам днесь; и остави нам долги наша, якоже и мы оставляем должником нашим; и не введи нас во искушение, но избави нас от лукаваго.',
                'sort_order' => 11,
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'language_code' => 'ru',
                'category' => 'common',
                'liturgy_key' => null,
                'title' => 'Царю Небесный',
                'short_title' => 'Царю Небесный',
                'body' => 'Царю Небесный, Утешителю, Душе истины, Иже везде сый и вся исполняяй, Сокровище благих и жизни Подателю, прииди и вселися в ны, и очисти ны от всякия скверны, и спаси, Блаже, души наша.',
                'sort_order' => 20,
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'language_code' => 'ru',
                'category' => 'common',
                'liturgy_key' => null,
                'title' => 'Богородице Дево',
                'short_title' => 'Богородице Дево',
                'body' => 'Богородице Дево, радуйся, Благодатная Марие, Господь с Тобою; благословенна Ты в женах и благословен плод чрева Твоего, яко Спаса родила еси душ наших.',
                'sort_order' => 30,
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'language_code' => 'ru',
                'category' => 'morning',
                'liturgy_key' => null,
                'title' => 'Краткая утренняя молитва',
                'short_title' => 'Утренняя',
                'body' => 'Господи, благослови начинающийся день. Просвети ум мой, укрепи сердце мое и направь дела мои ко благу и спасению.',
                'sort_order' => 100,
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'language_code' => 'ru',
                'category' => 'evening',
                'liturgy_key' => null,
                'title' => 'Краткая вечерняя молитва',
                'short_title' => 'Вечерняя',
                'body' => 'Господи Боже наш, прости мне согрешения прошедшего дня, сохрани меня в мире и тишине ночи и даруй мне пробуждение ко славе Твоей.',
                'sort_order' => 200,
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'language_code' => 'ru',
                'category' => 'communion_before',
                'liturgy_key' => null,
                'title' => 'Перед Святым Причащением',
                'short_title' => 'Перед Причастием',
                'body' => 'Верую, Господи, и исповедую, яко Ты еси воистину Христос, Сын Бога Живаго, пришедый в мир грешныя спасти, от нихже первый есмь аз.',
                'sort_order' => 300,
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'language_code' => 'ru',
                'category' => 'communion_after',
                'liturgy_key' => null,
                'title' => 'После Святого Причащения',
                'short_title' => 'После Причастия',
                'body' => 'Слава Тебе, Боже. Слава Тебе, Боже. Слава Тебе, Боже. Благодарю Тя, Господи Боже мой, яко не отринул мя еси грешнаго.',
                'sort_order' => 400,
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('prayers');
    }
};
