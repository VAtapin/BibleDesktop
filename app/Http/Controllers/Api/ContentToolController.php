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

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ContentToolController extends Controller
{
    public function usefulLinks(): JsonResponse
    {
        if (! Schema::hasTable('useful_links')) {
            return response()->json(['data' => []]);
        }

        $links = DB::table('useful_links')
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'slug', 'title', 'description', 'url', 'category', 'icon', 'cover_image_url'])
            ->map(fn ($link): array => [
                'id' => (int) $link->id,
                'slug' => (string) $link->slug,
                'title' => (string) $link->title,
                'description' => $link->description === null ? null : (string) $link->description,
                'url' => (string) $link->url,
                'category' => (string) $link->category,
                'icon' => $link->icon === null ? null : (string) $link->icon,
                'cover_image_url' => $this->publicAssetUrl($link->cover_image_url ?? null),
            ]);

        return response()->json(['data' => $links]);
    }

    public function faithQuestions(): JsonResponse
    {
        if (! Schema::hasTable('faith_questions')) {
            return response()->json(['data' => []]);
        }

        $questions = DB::table('faith_questions')
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->orderBy('question')
            ->get(['id', 'slug', 'category', 'question', 'answer_html', 'source_url'])
            ->map(fn ($question): array => [
                'id' => (int) $question->id,
                'slug' => (string) $question->slug,
                'category' => (string) $question->category,
                'question' => (string) $question->question,
                'answer_html' => (string) $question->answer_html,
                'source_url' => $question->source_url === null ? null : (string) $question->source_url,
            ]);

        return response()->json(['data' => $questions]);
    }

    public function recipeCategories(): JsonResponse
    {
        if (! Schema::hasTable('recipe_categories')) {
            return response()->json(['data' => []]);
        }

        $categories = DB::table('recipe_categories')
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'slug', 'name', 'description'])
            ->map(fn ($category): array => [
                'id' => (int) $category->id,
                'slug' => (string) $category->slug,
                'name' => (string) $category->name,
                'description' => $category->description === null ? null : (string) $category->description,
            ]);

        return response()->json(['data' => $categories]);
    }

    public function recipes(Request $request): JsonResponse
    {
        if (! Schema::hasTable('recipes') || ! Schema::hasTable('recipe_categories')) {
            return response()->json(['data' => []]);
        }

        $category = trim((string) $request->query('category', ''));
        $fastingRule = trim((string) $request->query('fasting_rule', ''));

        $recipes = DB::table('recipes')
            ->join('recipe_categories', 'recipe_categories.id', '=', 'recipes.recipe_category_id')
            ->where('recipes.status', 'approved')
            ->where('recipes.is_public', true)
            ->when($category !== '', fn ($query) => $query->where('recipe_categories.slug', $category))
            ->when($fastingRule !== '', fn ($query) => $query->where('recipes.fasting_rule', $fastingRule))
            ->orderBy('recipes.sort_order')
            ->orderBy('recipes.title')
            ->get([
                'recipes.id',
                'recipes.title',
                'recipes.summary',
                'recipes.cover_image_url',
                'recipes.fasting_rule',
                'recipe_categories.name as category_name',
                'recipe_categories.slug as category_slug',
            ])
            ->map(fn ($recipe): array => [
                'id' => (int) $recipe->id,
                'title' => (string) $recipe->title,
                'summary' => $recipe->summary === null ? null : (string) $recipe->summary,
                'cover_image_url' => $this->publicAssetUrl($recipe->cover_image_url),
                'fasting_rule' => $recipe->fasting_rule === null ? null : (string) $recipe->fasting_rule,
                'category' => [
                    'slug' => (string) $recipe->category_slug,
                    'name' => (string) $recipe->category_name,
                ],
            ]);

        return response()->json(['data' => $recipes]);
    }

    public function recipe(int $recipe): JsonResponse
    {
        if (! Schema::hasTable('recipes') || ! Schema::hasTable('recipe_categories')) {
            abort(404);
        }

        $hasServings = Schema::hasColumn('recipes', 'servings');

        $row = DB::table('recipes')
            ->join('recipe_categories', 'recipe_categories.id', '=', 'recipes.recipe_category_id')
            ->where('recipes.status', 'approved')
            ->where('recipes.is_public', true)
            ->where('recipes.id', $recipe)
            ->first([
                'recipes.id',
                'recipes.title',
                'recipes.summary',
                DB::raw($hasServings ? 'recipes.servings' : '4 as servings'),
                'recipes.ingredients',
                'recipes.cover_image_url',
                'recipes.youtube_url',
                'recipes.fasting_rule',
                'recipe_categories.name as category_name',
                'recipe_categories.slug as category_slug',
            ]);

        abort_if(! $row, 404);

        $steps = Schema::hasTable('recipe_steps') ? DB::table('recipe_steps')
            ->where('recipe_id', $recipe)
            ->orderBy('step_number')
            ->get(['step_number', 'body', 'image_url'])
            ->map(fn ($step): array => [
                'step_number' => (int) $step->step_number,
                'body' => (string) $step->body,
                'image_url' => $this->publicAssetUrl($step->image_url),
            ]) : collect();

        $ingredients = Schema::hasTable('recipe_ingredients') ? DB::table('recipe_ingredients')
            ->where('recipe_id', $recipe)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['name', 'amount', 'unit', 'note'])
            ->map(fn ($ingredient): array => [
                'name' => (string) $ingredient->name,
                'amount' => $ingredient->amount === null ? null : (float) $ingredient->amount,
                'unit' => $ingredient->unit === null ? null : (string) $ingredient->unit,
                'note' => $ingredient->note === null ? null : (string) $ingredient->note,
            ]) : collect();

        return response()->json(['data' => [
            'id' => (int) $row->id,
            'title' => (string) $row->title,
            'summary' => $row->summary === null ? null : (string) $row->summary,
            'servings' => (int) $row->servings,
            'ingredients' => $row->ingredients === null ? null : (string) $row->ingredients,
            'ingredient_items' => $ingredients,
            'cover_image_url' => $this->publicAssetUrl($row->cover_image_url),
            'youtube_url' => $row->youtube_url === null ? null : (string) $row->youtube_url,
            'fasting_rule' => $row->fasting_rule === null ? null : (string) $row->fasting_rule,
            'category' => [
                'slug' => (string) $row->category_slug,
                'name' => (string) $row->category_name,
            ],
            'steps' => $steps,
        ]]);
    }

    public function storeRecipe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recipe_category_id' => ['required', 'integer', 'exists:recipe_categories,id'],
            'title' => ['required', 'string', 'max:220'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'ingredients' => ['nullable', 'string', 'max:5000'],
        ]);

        $user = Auth::user();
        $trusted = (bool) ($user?->is_trusted_recipe_author ?? false);
        $recipeId = DB::table('recipes')->insertGetId([
            'recipe_category_id' => (int) $data['recipe_category_id'],
            'user_id' => $user?->id,
            'title' => (string) $data['title'],
            'summary' => $data['summary'] ?? null,
            'ingredients' => $data['ingredients'] ?? null,
            'status' => $trusted ? 'approved' : 'pending',
            'is_public' => $trusted,
            'sort_order' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['data' => ['id' => $recipeId, 'status' => $trusted ? 'approved' : 'pending']], 201);
    }

    public function quizzes(): JsonResponse
    {
        if (! Schema::hasTable('quizzes')) {
            return response()->json(['data' => []]);
        }

        $quizzes = DB::table('quizzes')
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'slug', 'title', 'description'])
            ->map(fn ($quiz): array => [
                'id' => (int) $quiz->id,
                'slug' => (string) $quiz->slug,
                'title' => (string) $quiz->title,
                'description' => $quiz->description === null ? null : (string) $quiz->description,
            ]);

        return response()->json(['data' => $quizzes]);
    }

    public function quiz(int $quiz): JsonResponse
    {
        if (! Schema::hasTable('quizzes') || ! Schema::hasTable('quiz_questions')) {
            abort(404);
        }

        $row = DB::table('quizzes')
            ->where('is_public', true)
            ->where('id', $quiz)
            ->first(['id', 'slug', 'title', 'description']);

        abort_if(! $row, 404);

        $questionColumns = [
            'id',
            'question',
            'explanation',
        ];

        foreach (['answer_type', 'image_path', 'recommendation_type', 'recommended_prayer_id', 'recommended_passage_ref', 'recommendation_text'] as $column) {
            if (Schema::hasColumn('quiz_questions', $column)) {
                $questionColumns[] = $column;
            }
        }

        $questions = DB::table('quiz_questions')
            ->where('quiz_id', $quiz)
            ->orderBy('sort_order')
            ->get($questionColumns)
            ->map(function ($question): array {
                $answerColumns = ['id', 'answer', 'is_correct'];

                foreach (['recommendation_type', 'recommended_prayer_id', 'recommended_passage_ref', 'recommendation_text'] as $column) {
                    if (Schema::hasColumn('quiz_answers', $column)) {
                        $answerColumns[] = $column;
                    }
                }

                $answers = Schema::hasTable('quiz_answers') ? DB::table('quiz_answers')
                    ->where('quiz_question_id', $question->id)
                    ->orderBy('sort_order')
                    ->get($answerColumns)
                    ->map(fn ($answer): array => [
                        'id' => (int) $answer->id,
                        'answer' => (string) $answer->answer,
                        'is_correct' => (bool) $answer->is_correct,
                        'recommendation' => $this->recommendationPayload($answer),
                    ]) : collect();

                return [
                    'id' => (int) $question->id,
                    'question' => (string) $question->question,
                    'answer_type' => (string) ($question->answer_type ?? 'single'),
                    'image_url' => $this->publicAssetUrl($question->image_path ?? null),
                    'explanation' => $question->explanation === null ? null : (string) $question->explanation,
                    'recommendation' => $this->recommendationPayload($question),
                    'answers' => $answers,
                ];
            });

        return response()->json(['data' => [
            'id' => (int) $row->id,
            'slug' => (string) $row->slug,
            'title' => (string) $row->title,
            'description' => $row->description === null ? null : (string) $row->description,
            'questions' => $questions,
        ]]);
    }

    public function tours(): JsonResponse
    {
        if (! Schema::hasTable('virtual_tours')) {
            return response()->json(['data' => []]);
        }

        $tours = DB::table('virtual_tours')
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get(['id', 'slug', 'title', 'description', 'cover_image_url', 'tour_url'])
            ->map(fn ($tour): array => [
                'id' => (int) $tour->id,
                'slug' => (string) $tour->slug,
                'title' => (string) $tour->title,
                'description' => $tour->description === null ? null : (string) $tour->description,
                'cover_image_url' => $this->publicAssetUrl($tour->cover_image_url),
                'tour_url' => (string) $tour->tour_url,
            ]);

        return response()->json(['data' => $tours]);
    }

    private function publicAssetUrl(mixed $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    private function recommendationPayload(object $row): ?array
    {
        $type = trim((string) ($row->recommendation_type ?? ''));
        $hasRecommendation = $type !== '' && $type !== 'none'
            || ! empty($row->recommended_prayer_id)
            || ! empty($row->recommended_passage_ref)
            || trim((string) ($row->recommendation_text ?? '')) !== '';

        if (! $hasRecommendation) {
            return null;
        }

        return [
            'type' => $type === '' ? 'text' : $type,
            'prayer_id' => empty($row->recommended_prayer_id) ? null : (int) $row->recommended_prayer_id,
            'passage_ref' => empty($row->recommended_passage_ref) ? null : (string) $row->recommended_passage_ref,
            'text' => empty($row->recommendation_text) ? null : (string) $row->recommendation_text,
        ];
    }
}
