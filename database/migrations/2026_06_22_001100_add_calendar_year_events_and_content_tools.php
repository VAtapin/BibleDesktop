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
        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->unsignedSmallInteger('start_year')->nullable()->after('date_rule_type');
            $table->unsignedSmallInteger('end_year')->nullable()->after('start_year');
            $table->index(['date_rule_type', 'start_year', 'start_month', 'start_day'], 'calendar_events_year_date_index');
        });

        Schema::table('prayers', function (Blueprint $table): void {
            $table->string('source_url', 500)->nullable()->after('body');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_trusted_recipe_author')->default(false)->after('settings_json');
        });

        Schema::create('recipe_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('name', 180);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });

        Schema::create('prayer_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('prayer_id')->constrained()->cascadeOnDelete();
            $table->string('title', 180)->nullable();
            $table->longText('body');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['prayer_id', 'sort_order']);
        });

        Schema::create('recipes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('recipe_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 220);
            $table->text('summary')->nullable();
            $table->text('ingredients')->nullable();
            $table->string('cover_image_url', 500)->nullable();
            $table->string('youtube_url', 500)->nullable();
            $table->string('fasting_rule', 60)->nullable();
            $table->string('status', 40)->default('pending');
            $table->boolean('is_public')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['status', 'is_public', 'sort_order']);
            $table->index(['fasting_rule', 'status']);
        });

        Schema::create('recipe_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('step_number');
            $table->text('body');
            $table->string('image_url', 500)->nullable();
            $table->timestamps();

            $table->unique(['recipe_id', 'step_number']);
        });

        Schema::create('quizzes', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('title', 220);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });

        Schema::create('quiz_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->text('question');
            $table->text('explanation')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('quiz_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quiz_question_id')->constrained()->cascadeOnDelete();
            $table->string('answer', 500);
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('virtual_tours', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('title', 220);
            $table->text('description')->nullable();
            $table->string('cover_image_url', 500)->nullable();
            $table->string('tour_url', 500);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_public')->default(true);
            $table->timestamps();
        });

        $now = now();

        $memoryTypeId = DB::table('calendar_event_types')->insertGetId([
            'code' => 'memory_date_year',
            'legacy_type' => null,
            'name' => 'Памятные даты года',
            'typicon_symbol' => null,
            'color' => null,
            'is_fasting' => false,
            'is_visible' => true,
            'description' => 'Юбилейные и памятные даты конкретного календарного года.',
            'sort_order' => 85,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $fastTypeId = DB::table('calendar_event_types')->insertGetId([
            'code' => 'fasting_rule',
            'legacy_type' => null,
            'name' => 'Пост и трапеза',
            'typicon_symbol' => null,
            'color' => null,
            'is_fasting' => true,
            'is_visible' => true,
            'description' => 'Правило поста и трапезы на день.',
            'sort_order' => 95,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('calendar_events')->insert(array_merge(
            $this->memoryDates($memoryTypeId, $now),
            $this->fastingEvents($fastTypeId, $now),
        ));

        $this->seedDayPrayers($now);
        DB::table('recipe_categories')->insert($this->recipeCategories($now));
        $this->seedRecipes($now);
        $this->seedQuizzes($now);
        DB::table('virtual_tours')->insert([
            [
                'slug' => 'georg-kloster-kirche',
                'title' => 'Храм Святого Георгия',
                'description' => '360° тур по монастырскому храму.',
                'cover_image_url' => null,
                'tour_url' => 'https://georg-kloster.ru/360/klosterkirche/',
                'sort_order' => 10,
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('virtual_tours');
        Schema::dropIfExists('quiz_answers');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quizzes');
        Schema::dropIfExists('recipe_steps');
        Schema::dropIfExists('recipes');
        Schema::dropIfExists('prayer_sections');
        Schema::dropIfExists('recipe_categories');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_trusted_recipe_author');
        });

        Schema::table('prayers', function (Blueprint $table): void {
            $table->dropColumn('source_url');
        });

        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->dropIndex('calendar_events_year_date_index');
            $table->dropColumn(['start_year', 'end_year']);
        });

        DB::table('calendar_event_types')->whereIn('code', ['memory_date_year', 'fasting_rule'])->delete();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function memoryDates(int $typeId, mixed $now): array
    {
        $source = 'https://azbyka.ru/days/p-pamjatnye-daty-2026';
        $rows = [
            [12, 8, '1925 лет - преставления священномученика Климента, папы Римского (8 декабря 101)'],
            [12, 25, '1775 лет - преставления священномученика Александра, епископа Иерусалимского (25 декабря 251)'],
            [9, 17, '1775 лет - преставления священномученика Вавилы, епископа Великой Антиохии, и с ним трех отроков и матери их Христодулы (17 сентября 251)'],
            [10, 2, '1750 лет - преставления мучеников Трофима, Савватия и Доримедонта (2 октября 276)'],
            [3, 19, '1700 лет - обретения Честного Креста и гвоздей святой царицею Еленою во Иерусалиме (19 марта 326)'],
            [4, 13, '1700 лет - преставления священномученика Ипатия, епископа Гангрского (13 апреля ок. 326)'],
            [6, 17, '1700 лет - преставления святителя Митрофана, патриарха Константинопольского (17 июня ок. 326)'],
            [5, 20, '1675 лет - явления на небе Креста Господня в Иерусалиме (20 мая 351)'],
            [4, 22, '1650 лет - преставления преподобномученика Вадима архимандрита (22 апреля 376)'],
            [7, 24, '1575 лет - явления чуда великомученицы Евфимии всехвальной, имже Православие утвердися (24 июля 451)'],
            [7, 29, '1575 лет - со времени IV Вселенского Собора (29 июля 451)'],
            [4, 26, '1550 лет - преставления мученицы Фомаиды Египетской (26 апреля 476)'],
            [7, 17, '1475 лет - преставления преподобной Марфы, матери Симеона Дивногорца (17 июля 551)'],
            [6, 10, '1450 лет - преставления святителя Германа, епископа Парижского (10 июня 576)'],
            [1, 23, '1425 лет - преставления преподобного Дометиана, епископа Мелитинского (23 января 601)'],
            [11, 11, '1200 лет - преставления преподобной Анны Вифинской (11 ноября 826)'],
            [11, 24, '1200 лет - преставления преподобного Феодора Студита, исповедника, песнописца (24 ноября 826)'],
            [5, 23, '800 лет - преставления святителя Симона, епископа Владимирского и Суздальского (23 мая 1226)'],
            [1, 3, '700 лет - преставления святителя Петра Московского, митрополита Киевского и всея Руси, чудотворца (3 января 1326)'],
            [5, 18, '600 лет - преставления преподобномученика Ефрема Нового (18 мая 1426)'],
            [6, 9, '600 лет - преставления преподобного Ферапонта Белоезерского, Можайского (9 июня 1426)'],
            [11, 30, '600 лет - преставления преподобного Никона, игумена Радонежского, ученика преподобного Сергия (30 ноября 1426)'],
            [4, 10, '550 лет - преставления преподобного Илариона Псковоезерского, Гдовского (10 апреля 1476)'],
            [12, 23, '550 лет - преставления блаженного Стефана Бранковича, правителя Сербского (23 декабря 1476)'],
            [6, 21, '525 лет - обретения мощей благоверных князей Василия и Константина Ярославских (21 июня 1501)'],
            [5, 1, '500 лет - преставления мученика Иоанна Нового из Янины (1 мая 1526)'],
            [3, 13, '450 лет - преставления блаженного Николая, Христа ради юродивого, Псковского (13 марта 1576)'],
            [4, 24, '450 лет - преставления святителя Варсонофия, епископа Тверского (24 апреля 1576)'],
            [12, 2, '400 лет - обретения мощей преподобномученика Адриана Пошехонского, Ярославского (2 декабря 1626)'],
            [11, 10, '375 лет - преставления преподобного Иова, игумена Почаевского (10 ноября 1651)'],
            [11, 25, '375 лет - преставления преподобного Нила Мироточивого, Афонского (25 ноября 1651)'],
            [8, 11, '250 лет - преставления мученика Даниила Черкасского (11 августа 1776)'],
            [10, 8, '250 лет - преставления преподобной Досифеи затворницы, Киевской (8 октября 1776)'],
            [1, 14, '225 лет - преставления святителя Афанасия Полтавского, чудотворца (14 января 1801)'],
            [7, 22, '125 лет - преставления преподобного Гавриила Афонского, настоятеля афонского Ильинского скита (22 июля 1901)'],
            [3, 1, '100 лет - преставления святителя Московского Макария (Невского) (1 марта 1926)'],
            [8, 6, '75 лет - преставления святого Иоанна Калинина исповедника, пресвитера (6 августа 1951)'],
            [6, 11, '65 лет - преставления святителя Луки исповедника, архиепископа Симферопольского (11 июня 1961)'],
            [4, 19, '60 лет - преставления преподобного Севастиана Карагандинского, исповедника (19 апреля 1966)'],
            [7, 2, '60 лет - преставления святителя Иоанна Максимовича, архиепископа Шанхайского и Сан-Францисского (2 июля 1966)'],
            [9, 22, '50 лет - преставления преподобного Серафима (Романцова), Глинского (22 сентября 1976)'],
            [1, 15, '35 лет - второго обретения мощей преподобного Серафима Саровского (15 января 1991)'],
            [5, 28, '35 лет - обретения мощей преподобного Арсения Коневского (28 мая 1991)'],
            [8, 26, '35 лет - второго обретения мощей святителя Тихона, епископа Воронежского, Задонского, чудотворца (26 августа 1991)'],
            [3, 18, '30 лет - обретения мощей святителя Луки исповедника, архиепископа Симферопольского (18 марта 1996)'],
            [7, 4, '30 лет - обретения мощей преподобного Максима Грека (4 июля 1996)'],
            [6, 5, '25 лет - обретения мощей мучениц Евдокии Шейковой, Дарии Тимагиной, Дарии Улыбиной и Марии Неизвестной (5 июня 2001)'],
            [7, 20, '25 лет - обретения мощей преподобного Герасима Болдинского (20 июля 2001)'],
            [8, 5, '25 лет - прославления праведного воина Феодора Ушакова (5 августа 2001)'],
            [9, 18, '25 лет - обретения мощей преподобного Александра (Уродова) исповедника (18 сентября 2001)'],
            [9, 29, '25 лет - перенесения мощей праведного Алексия Московского (29 сентября 2001)'],
            [10, 31, '25 лет - обретения мощей преподобного Иосифа, игумена Волоцкого, чудотворца (31 октября 2001)'],
            [11, 20, '80 лет - со дня рождения Святейшего Патриарха Московского и всея Руси Кирилла (7/20 ноября 1946)'],
            [3, 14, '50 лет - со дня архиерейской хиротонии Святейшего Патриарха Московского и всея Руси Кирилла (1/14 марта 1976)'],
        ];

        return array_map(fn (array $row): array => [
            'calendar_event_type_id' => $typeId,
            'name' => $row[2],
            'legacy_type' => null,
            'date_rule_type' => 'fixed_year',
            'start_year' => 2026,
            'end_year' => null,
            'start_month' => $row[0],
            'start_day' => $row[1],
            'start_offset' => null,
            'end_month' => null,
            'end_day' => null,
            'end_offset' => null,
            'metadata_json' => json_encode(['source' => $source, 'year' => 2026], JSON_UNESCAPED_UNICODE),
            'created_at' => $now,
            'updated_at' => $now,
        ], $rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fastingEvents(int $typeId, mixed $now): array
    {
        $source = 'https://azbyka.ru/days/p-kalendar-postov-i-trapez';
        $rows = [
            ['Великий пост', 'pascha_relative_range', null, null, -48, null, null, -7, 'great_lent', 'По понедельникам, вторникам и четвергам - горячая пища без масла; среда и пятница - сухоядение; суббота и воскресенье - пища с растительным маслом.'],
            ['Страстная седмица', 'pascha_relative_range', null, null, -6, null, null, -1, 'holy_week', 'Строгий пост; в Великую Пятницу традиционно воздерживаются от пищи до выноса Плащаницы.'],
            ['Апостольский пост', 'pascha_relative_range', null, null, 57, 7, 11, null, 'apostles_fast', 'Приходская практика: среда и пятница - горячая пища с маслом; в остальные дни разрешается рыба.'],
            ['Успенский пост', 'fixed_range', 8, 14, null, 8, 27, null, 'dormition_fast', 'Среда и пятница - сухоядение; понедельник, вторник, четверг - горячая пища без масла; суббота и воскресенье - пища с маслом.'],
            ['Рождественский пост: до 19 декабря', 'fixed_range', 11, 28, null, 12, 19, null, 'nativity_fast_fish', 'По приходской практике рыба разрешается в понедельник, вторник, четверг, субботу и воскресенье.'],
            ['Рождественский пост: 20 декабря - 1 января', 'fixed_range', 12, 20, null, 1, 1, null, 'nativity_fast_oil', 'Понедельник, вторник и четверг - пища с маслом; среда и пятница - без масла; суббота и воскресенье - рыба.'],
            ['Рождественский пост: предпразднство Рождества', 'fixed_range', 1, 2, null, 1, 6, null, 'nativity_fast_strict', 'Понедельник, вторник и четверг - без масла; среда и пятница - сухоядение; суббота и воскресенье - с маслом.'],
            ['Крещенский сочельник', 'fixed_year', 1, 18, null, null, null, null, 'strict_fast', 'Однодневный строгий пост.'],
            ['Усекновение главы Иоанна Предтечи', 'fixed_year', 9, 11, null, null, null, null, 'strict_fast', 'Однодневный пост.'],
            ['Воздвижение Креста Господня', 'fixed_year', 9, 27, null, null, null, null, 'strict_fast', 'Однодневный пост.'],
        ];

        return array_map(fn (array $row): array => [
            'calendar_event_type_id' => $typeId,
            'name' => $row[0],
            'legacy_type' => null,
            'date_rule_type' => $row[1],
            'start_year' => $row[1] === 'fixed_year' ? 2026 : null,
            'end_year' => null,
            'start_month' => $row[2],
            'start_day' => $row[3],
            'start_offset' => $row[4],
            'end_month' => $row[5],
            'end_day' => $row[6],
            'end_offset' => $row[7],
            'metadata_json' => json_encode([
                'source' => $source,
                'fasting_rule' => $row[8],
                'meal_note' => $row[9],
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => $now,
            'updated_at' => $now,
        ], $rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function seedDayPrayers(mixed $now): void
    {
        $source = 'https://azbyka.ru/molitvoslov/#ch2';

        $rows = [
            [
                'language_code' => 'ru',
                'category' => 'day',
                'liturgy_key' => null,
                'title' => 'На принятие просфоры и святой воды',
                'short_title' => 'Просфора и вода',
                'body' => 'Господи Боже мой, да будет дар Твой святый и святая Твоя вода во оставление грехов моих, в просвещение ума моего, в укрепление душевных и телесных сил моих, во здравие души и тела моего, в покорение страстей и немощей моих по беспредельному милосердию Твоему молитвами Пречистыя Твоея Матери и всех святых Твоих. Аминь.',
                'source_url' => $source,
                'sort_order' => 500,
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'language_code' => 'ru',
                'category' => 'day',
                'liturgy_key' => null,
                'title' => 'Молитва Оптинских старцев',
                'short_title' => 'Оптинских старцев',
                'body' => 'Господи, дай мне с душевным спокойствием встретить все, что принесет мне наступающий день. Дай мне всецело предаться воле Твоей святой. На всякий час сего дня во всем наставь и поддержи меня.',
                'source_url' => $source,
                'sort_order' => 510,
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'language_code' => 'ru',
                'category' => 'day',
                'liturgy_key' => null,
                'title' => 'Перед вкушением пищи',
                'short_title' => 'Перед пищей',
                'body' => 'Отче наш, Иже еси на небесех! Да святится имя Твое, да приидет Царствие Твое, да будет воля Твоя, яко на небеси и на земли. Хлеб наш насущный даждь нам днесь; и остави нам долги наша, якоже и мы оставляем должником нашим; и не введи нас во искушение, но избави нас от лукаваго.',
                'source_url' => $source,
                'sort_order' => 520,
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'language_code' => 'ru',
                'category' => 'day',
                'liturgy_key' => null,
                'title' => 'После вкушения пищи',
                'short_title' => 'После пищи',
                'body' => 'Благодарим Тя, Христе Боже наш, яко насытил еси нас земных Твоих благ; не лиши нас и Небеснаго Твоего Царствия, но яко посреде учеников Твоих пришел еси, Спасе, мир даяй им, прииди к нам и спаси нас.',
                'source_url' => $source,
                'sort_order' => 530,
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'language_code' => 'ru',
                'category' => 'day',
                'liturgy_key' => null,
                'title' => 'Перед началом всякого дела',
                'short_title' => 'Перед делом',
                'body' => 'Господи Иисусе Христе, Сыне Единородный Безначальнаго Твоего Отца, Ты рекл еси пречистыми усты Твоими, яко без Мене не можете творити ничесоже. Господи мой, Господи, верою объем в душе моей и сердце Тобою реченная, припадаю Твоей благости: помози ми, грешному, сие дело, мною начинаемое, о Тебе Самом совершити.',
                'source_url' => $source,
                'sort_order' => 540,
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'language_code' => 'ru',
                'category' => 'day',
                'liturgy_key' => null,
                'title' => 'По окончании всякого дела',
                'short_title' => 'После дела',
                'body' => 'Исполнение всех благих Ты еси, Христе мой, исполни радости и веселия душу мою и спаси мя, яко Един многомилостив.',
                'source_url' => $source,
                'sort_order' => 550,
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($rows as $index => $row) {
            $prayerId = DB::table('prayers')->insertGetId($row);

            DB::table('prayer_sections')->insert([
                'prayer_id' => $prayerId,
                'title' => (string) $row['title'],
                'body' => (string) $row['body'],
                'sort_order' => ($index + 1) * 10,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recipeCategories(mixed $now): array
    {
        return [
            ['slug' => 'postnye-retsepty', 'name' => 'Постные рецепты', 'description' => 'Рецепты для постных дней.', 'sort_order' => 10, 'is_public' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'suhoyadenie', 'name' => 'Сухоядение', 'description' => 'Простые блюда без варки и масла.', 'sort_order' => 20, 'is_public' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'bez-masla', 'name' => 'Горячее без масла', 'description' => 'Постные блюда без растительного масла.', 'sort_order' => 30, 'is_public' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 's-maslom', 'name' => 'С растительным маслом', 'description' => 'Постные блюда с растительным маслом.', 'sort_order' => 40, 'is_public' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'ryba', 'name' => 'Рыба', 'description' => 'Постные дни, когда разрешается рыба.', 'sort_order' => 50, 'is_public' => true, 'created_at' => $now, 'updated_at' => $now],
        ];
    }

    private function seedRecipes(mixed $now): void
    {
        $categoryId = (int) DB::table('recipe_categories')->where('slug', 'postnye-retsepty')->value('id');
        $recipeId = DB::table('recipes')->insertGetId([
            'recipe_category_id' => $categoryId,
            'user_id' => null,
            'title' => 'Постная гречневая каша с грибами',
            'summary' => 'Простой базовый рецепт для постного стола.',
            'ingredients' => "Гречневая крупа - 1 стакан\nГрибы - 250 г\nЛук - 1 шт.\nСоль - по вкусу\nРастительное масло - если разрешено уставом дня",
            'cover_image_url' => null,
            'youtube_url' => null,
            'fasting_rule' => 'oil',
            'status' => 'approved',
            'is_public' => true,
            'sort_order' => 10,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('recipe_steps')->insert([
            ['recipe_id' => $recipeId, 'step_number' => 1, 'body' => 'Промойте гречку и отварите до готовности.', 'image_url' => null, 'created_at' => $now, 'updated_at' => $now],
            ['recipe_id' => $recipeId, 'step_number' => 2, 'body' => 'Нарежьте грибы и лук, потушите до мягкости.', 'image_url' => null, 'created_at' => $now, 'updated_at' => $now],
            ['recipe_id' => $recipeId, 'step_number' => 3, 'body' => 'Смешайте гречку с грибами, посолите и подавайте теплой.', 'image_url' => null, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    private function seedQuizzes(mixed $now): void
    {
        foreach ($this->quizSeedData() as $quizData) {
            $quizId = DB::table('quizzes')->insertGetId([
                'slug' => $quizData['slug'],
                'title' => $quizData['title'],
                'description' => $quizData['description'],
                'sort_order' => $quizData['sort_order'],
                'is_public' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($quizData['questions'] as $index => $questionData) {
                $questionId = DB::table('quiz_questions')->insertGetId([
                    'quiz_id' => $quizId,
                    'question' => $questionData[0],
                    'explanation' => $questionData[4],
                    'sort_order' => ($index + 1) * 10,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach (array_slice($questionData, 1, 3) as $answerIndex => $answer) {
                    DB::table('quiz_answers')->insert([
                        'quiz_question_id' => $questionId,
                        'answer' => $answer,
                        'is_correct' => $answerIndex === 0,
                        'sort_order' => ($answerIndex + 1) * 10,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    /**
     * @return list<array{slug: string, title: string, description: string, sort_order: int, questions: list<array{string, string, string, string, string}>}>
     */
    private function quizSeedData(): array
    {
        $baseQuestions = [
            ['Кто построил ковчег?', 'Ной', 'Авраам', 'Моисей', 'Ковчег построил праведный Ной.'],
            ['Кто был первым человеком?', 'Адам', 'Авель', 'Сиф', 'Первым человеком в книге Бытия назван Адам.'],
            ['Кто вывел Израиль из Египта?', 'Моисей', 'Иисус Навин', 'Самуил', 'Моисей был призван Богом вывести народ из Египта.'],
            ['Где родился Иисус Христос?', 'В Вифлееме', 'В Назарете', 'В Иерусалиме', 'Евангелие указывает Вифлеем как место Рождества Христова.'],
            ['Кто крестил Господа Иисуса Христа?', 'Иоанн Предтеча', 'Апостол Петр', 'Апостол Павел', 'Иоанн Предтеча крестил Господа в Иордане.'],
            ['Сколько Евангелий входит в Новый Завет?', 'Четыре', 'Три', 'Пять', 'В каноне Нового Завета четыре Евангелия.'],
            ['Кто написал большинство посланий Нового Завета?', 'Апостол Павел', 'Апостол Иоанн', 'Апостол Иаков', 'Большинство посланий традиционно связывается с апостолом Павлом.'],
            ['Как называется первая книга Библии?', 'Бытие', 'Исход', 'Псалтирь', 'Первая книга Писания - Бытие.'],
            ['Какой псалом начинается словами “Господь - Пастырь мой”?', 'Псалом 22', 'Псалом 50', 'Псалом 90', 'В русской синодальной нумерации это Псалом 22.'],
            ['Кто был предан за тридцать сребреников?', 'Иисус Христос', 'Иосиф', 'Иеремия', 'Евангелие говорит о предательстве Христа Иудой.'],
        ];

        return [
            ['slug' => 'osnovy-biblii', 'title' => 'Основы Библии', 'description' => 'Первые вопросы по Священному Писанию.', 'sort_order' => 10, 'questions' => $baseQuestions],
            ['slug' => 'vethiy-zavet', 'title' => 'Ветхий Завет', 'description' => 'Ключевые события и персонажи Ветхого Завета.', 'sort_order' => 20, 'questions' => $baseQuestions],
            ['slug' => 'noviy-zavet', 'title' => 'Новый Завет', 'description' => 'Евангелие и апостольская история.', 'sort_order' => 30, 'questions' => $baseQuestions],
            ['slug' => 'psaltir', 'title' => 'Псалтирь', 'description' => 'Вопросы по псалмам и молитвенной традиции.', 'sort_order' => 40, 'questions' => $baseQuestions],
            ['slug' => 'pravoslavnyy-kalendar', 'title' => 'Православный календарь', 'description' => 'Праздники, посты и богослужебный круг.', 'sort_order' => 50, 'questions' => $baseQuestions],
        ];
    }
};
