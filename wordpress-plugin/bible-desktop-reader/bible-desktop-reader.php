<?php
/**
 * Plugin Name: Bible Desktop Reader
 * Description: Lightweight Bible reader with module import, search, Strong numbers, and parallel places.
 * Version: 0.1.0
 * Author: Atapin Vladimir
 * Author URI: https://bible-media.de/
 * Text Domain: bible-desktop-reader
 */

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

if (! defined('ABSPATH')) {
    exit;
}

define('BD_READER_VERSION', '0.1.0');
define('BD_READER_FILE', __FILE__);
define('BD_READER_DIR', plugin_dir_path(__FILE__));
define('BD_READER_URL', plugin_dir_url(__FILE__));

register_activation_hook(__FILE__, 'bd_reader_install');

add_action('admin_menu', 'bd_reader_admin_menu');
add_action('admin_post_bd_reader_preview_import', 'bd_reader_handle_preview_import');
add_action('admin_post_bd_reader_import_module', 'bd_reader_handle_import_module');
add_shortcode('bible_desktop', 'bd_reader_shortcode');
add_action('wp_enqueue_scripts', 'bd_reader_register_assets');
add_action('wp_ajax_bd_reader_modules', 'bd_reader_ajax_modules');
add_action('wp_ajax_nopriv_bd_reader_modules', 'bd_reader_ajax_modules');
add_action('wp_ajax_bd_reader_chapter', 'bd_reader_ajax_chapter');
add_action('wp_ajax_nopriv_bd_reader_chapter', 'bd_reader_ajax_chapter');
add_action('wp_ajax_bd_reader_search', 'bd_reader_ajax_search');
add_action('wp_ajax_nopriv_bd_reader_search', 'bd_reader_ajax_search');
add_action('wp_ajax_bd_reader_strong', 'bd_reader_ajax_strong');
add_action('wp_ajax_nopriv_bd_reader_strong', 'bd_reader_ajax_strong');

/**
 * Create plugin-owned tables. WordPress keeps this database separate from Laravel.
 */
function bd_reader_install(): void
{
    global $wpdb;

    require_once ABSPATH.'wp-admin/includes/upgrade.php';

    $charset = $wpdb->get_charset_collate();
    $prefix = $wpdb->prefix.'bd_';

    dbDelta("
        CREATE TABLE {$prefix}modules (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            code VARCHAR(120) NOT NULL,
            name VARCHAR(255) NOT NULL,
            short_name VARCHAR(120) NULL,
            language_code VARCHAR(16) NOT NULL DEFAULT 'ru',
            description TEXT NULL,
            cover_id BIGINT UNSIGNED NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY code (code),
            KEY language_code (language_code)
        ) {$charset};
    ");

    dbDelta("
        CREATE TABLE {$prefix}books (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            module_id BIGINT UNSIGNED NOT NULL,
            slug VARCHAR(120) NOT NULL,
            name VARCHAR(180) NOT NULL,
            short_name VARCHAR(80) NULL,
            book_order INT UNSIGNED NOT NULL DEFAULT 0,
            chapters_count INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY module_book (module_id, slug),
            KEY module_order (module_id, book_order)
        ) {$charset};
    ");

    dbDelta("
        CREATE TABLE {$prefix}verses (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            module_id BIGINT UNSIGNED NOT NULL,
            book_id BIGINT UNSIGNED NOT NULL,
            chapter_number INT UNSIGNED NOT NULL,
            verse_number INT UNSIGNED NOT NULL,
            text LONGTEXT NOT NULL,
            text_plain LONGTEXT NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY verse_key (module_id, book_id, chapter_number, verse_number),
            KEY chapter_key (module_id, book_id, chapter_number),
            KEY search_key (module_id, chapter_number, verse_number)
        ) {$charset};
    ");

    dbDelta("
        CREATE TABLE {$prefix}strong_entries (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            number VARCHAR(20) NOT NULL,
            title VARCHAR(255) NULL,
            content LONGTEXT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY number (number)
        ) {$charset};
    ");

    dbDelta("
        CREATE TABLE {$prefix}cross_references (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            module_id BIGINT UNSIGNED NOT NULL,
            book_id BIGINT UNSIGNED NOT NULL,
            chapter_number INT UNSIGNED NOT NULL,
            verse_number INT UNSIGNED NOT NULL,
            target_ref VARCHAR(120) NOT NULL,
            target_text TEXT NULL,
            PRIMARY KEY (id),
            KEY source_key (module_id, book_id, chapter_number, verse_number)
        ) {$charset};
    ");
}

/**
 * Register admin pages for modules and imports.
 */
function bd_reader_admin_menu(): void
{
    add_menu_page(
        'Bible Desktop',
        'Bible Desktop',
        'manage_options',
        'bd-reader-modules',
        'bd_reader_admin_modules_page',
        'dashicons-book-alt',
        26
    );

    add_submenu_page(
        'bd-reader-modules',
        'Modules',
        'Modules',
        'manage_options',
        'bd-reader-modules',
        'bd_reader_admin_modules_page'
    );

    add_submenu_page(
        'bd-reader-modules',
        'Import',
        'Import',
        'manage_options',
        'bd-reader-import',
        'bd_reader_admin_import_page'
    );
}

/**
 * Render imported module cards in wp-admin.
 */
function bd_reader_admin_modules_page(): void
{
    global $wpdb;

    $modules = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bd_modules ORDER BY name ASC");
    ?>
    <div class="wrap bd-reader-admin">
        <h1>Bible Desktop modules</h1>
        <p>Use shortcode <code>[bible_desktop]</code> on any WordPress page.</p>
        <div class="bd-reader-admin-grid">
            <?php foreach ($modules as $module) : ?>
                <article class="bd-reader-admin-card">
                    <?php if ((int) $module->cover_id > 0) : ?>
                        <?php echo wp_get_attachment_image((int) $module->cover_id, 'medium'); ?>
                    <?php else : ?>
                        <div class="bd-reader-admin-cover">BD</div>
                    <?php endif; ?>
                    <h2><?php echo esc_html($module->name); ?></h2>
                    <p><strong><?php echo esc_html($module->short_name ?: $module->code); ?></strong> · <?php echo esc_html($module->language_code); ?></p>
                    <p><?php echo esc_html(wp_trim_words((string) $module->description, 28)); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

/**
 * Render upload and check/import controls.
 */
function bd_reader_admin_import_page(): void
{
    $preview = get_transient('bd_reader_last_preview_'.get_current_user_id());
    ?>
    <div class="wrap">
        <h1>Import Bible module</h1>
        <p>Upload a BibleQuote ZIP or a SQLite3 module. The plugin imports into its own WordPress tables.</p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
            <?php wp_nonce_field('bd_reader_import'); ?>
            <input type="hidden" name="action" value="bd_reader_preview_import">
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="bd_reader_module">Module file</label></th>
                    <td><input id="bd_reader_module" type="file" name="module_file" accept=".zip,.sqlite,.sqlite3,.SQLite3" required></td>
                </tr>
            </table>
            <?php submit_button('Check module'); ?>
        </form>

        <?php if (is_array($preview)) : ?>
            <hr>
            <h2>Last check result</h2>
            <table class="widefat striped">
                <tbody>
                    <tr><th>File</th><td><?php echo esc_html($preview['file']); ?></td></tr>
                    <tr><th>Format</th><td><?php echo esc_html($preview['format']); ?></td></tr>
                    <tr><th>Name</th><td><?php echo esc_html($preview['name']); ?></td></tr>
                    <tr><th>Language</th><td><?php echo esc_html($preview['language_code']); ?></td></tr>
                    <tr><th>Books</th><td><?php echo esc_html((string) $preview['books']); ?></td></tr>
                    <tr><th>Verses</th><td><?php echo esc_html((string) $preview['verses']); ?></td></tr>
                    <tr><th>Strong</th><td><?php echo $preview['has_strong'] ? 'yes' : 'no'; ?></td></tr>
                </tbody>
            </table>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('bd_reader_import'); ?>
                <input type="hidden" name="action" value="bd_reader_import_module">
                <input type="hidden" name="path" value="<?php echo esc_attr($preview['path']); ?>">
                <?php submit_button('Import module', 'primary'); ?>
            </form>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Store an upload and show its parse preview.
 */
function bd_reader_handle_preview_import(): void
{
    if (! current_user_can('manage_options') || ! check_admin_referer('bd_reader_import')) {
        wp_die('Forbidden');
    }

    if (! isset($_FILES['module_file'])) {
        wp_die('No file uploaded.');
    }

    require_once ABSPATH.'wp-admin/includes/file.php';
    $upload = wp_handle_upload($_FILES['module_file'], ['test_form' => false]);

    if (! empty($upload['error'])) {
        wp_die(esc_html($upload['error']));
    }

    $preview = bd_reader_scan_module((string) $upload['file']);
    set_transient('bd_reader_last_preview_'.get_current_user_id(), $preview, HOUR_IN_SECONDS);

    wp_safe_redirect(admin_url('admin.php?page=bd-reader-import'));
    exit;
}

/**
 * Import the checked module into WordPress tables.
 */
function bd_reader_handle_import_module(): void
{
    if (! current_user_can('manage_options') || ! check_admin_referer('bd_reader_import')) {
        wp_die('Forbidden');
    }

    $path = isset($_POST['path']) ? (string) wp_unslash($_POST['path']) : '';

    if ($path === '' || ! is_readable($path)) {
        wp_die('Import file is missing.');
    }

    $result = bd_reader_import_module($path);
    set_transient('bd_reader_last_preview_'.get_current_user_id(), $result, HOUR_IN_SECONDS);

    wp_safe_redirect(admin_url('admin.php?page=bd-reader-import'));
    exit;
}

/**
 * Register frontend assets.
 */
function bd_reader_register_assets(): void
{
    wp_register_style('bd-reader', BD_READER_URL.'assets/bible-desktop-reader.css', [], BD_READER_VERSION);
    wp_register_script('bd-reader', BD_READER_URL.'assets/bible-desktop-reader.js', [], BD_READER_VERSION, true);
}

/**
 * Render the frontend reader.
 */
function bd_reader_shortcode(): string
{
    wp_enqueue_style('bd-reader');
    wp_enqueue_script('bd-reader');
    wp_localize_script('bd-reader', 'BibleDesktopReader', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('bd_reader_ajax'),
    ]);

    return '<div class="bd-reader" data-bd-reader>
        <header class="bd-reader-toolbar">
            <select data-bd-module></select>
            <select data-bd-book></select>
            <select data-bd-chapter></select>
            <label><input type="checkbox" data-bd-strong-toggle> Strong</label>
            <input type="search" data-bd-search placeholder="Search Bible">
            <button type="button" data-bd-search-button>Search</button>
        </header>
        <main class="bd-reader-layout">
            <section class="bd-reader-text" data-bd-text></section>
            <aside class="bd-reader-side" data-bd-side></aside>
        </main>
    </div>';
}

/**
 * AJAX: return modules with nested books.
 */
function bd_reader_ajax_modules(): void
{
    bd_reader_verify_ajax();
    global $wpdb;

    $modules = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}bd_modules WHERE is_active = 1 ORDER BY name ASC", ARRAY_A);

    foreach ($modules as &$module) {
        $module['books'] = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, slug, name, short_name, chapters_count FROM {$wpdb->prefix}bd_books WHERE module_id = %d ORDER BY book_order ASC",
                (int) $module['id']
            ),
            ARRAY_A
        );
    }

    wp_send_json_success($modules);
}

/**
 * AJAX: return one chapter.
 */
function bd_reader_ajax_chapter(): void
{
    bd_reader_verify_ajax();
    global $wpdb;

    $moduleId = absint($_GET['module_id'] ?? 0);
    $bookId = absint($_GET['book_id'] ?? 0);
    $chapter = absint($_GET['chapter'] ?? 1);

    $verses = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT verse_number, text FROM {$wpdb->prefix}bd_verses WHERE module_id = %d AND book_id = %d AND chapter_number = %d ORDER BY verse_number ASC",
            $moduleId,
            $bookId,
            $chapter
        ),
        ARRAY_A
    );

    foreach ($verses as &$verse) {
        $verse['html'] = bd_reader_render_verse_text((string) $verse['text']);
    }

    wp_send_json_success($verses);
}

/**
 * AJAX: search imported verses.
 */
function bd_reader_ajax_search(): void
{
    bd_reader_verify_ajax();
    global $wpdb;

    $moduleId = absint($_GET['module_id'] ?? 0);
    $query = trim((string) wp_unslash($_GET['q'] ?? ''));

    if (mb_strlen($query) < 2) {
        wp_send_json_success([]);
    }

    $like = '%'.$wpdb->esc_like(mb_strtolower($query)).'%';
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT v.id, v.chapter_number, v.verse_number, v.text_plain, b.name AS book_name
             FROM {$wpdb->prefix}bd_verses v
             INNER JOIN {$wpdb->prefix}bd_books b ON b.id = v.book_id
             WHERE v.module_id = %d AND LOWER(v.text_plain) LIKE %s
             ORDER BY b.book_order ASC, v.chapter_number ASC, v.verse_number ASC
             LIMIT 50",
            $moduleId,
            $like
        ),
        ARRAY_A
    );

    wp_send_json_success($rows);
}

/**
 * AJAX: return Strong dictionary entry.
 */
function bd_reader_ajax_strong(): void
{
    bd_reader_verify_ajax();
    global $wpdb;

    $number = strtoupper(preg_replace('/[^GH0-9]/i', '', (string) ($_GET['number'] ?? '')));
    $entry = $wpdb->get_row(
        $wpdb->prepare("SELECT number, title, content FROM {$wpdb->prefix}bd_strong_entries WHERE number = %s", $number),
        ARRAY_A
    );

    if (! $entry) {
        $entry = [
            'number' => $number,
            'title' => 'Strong '.$number,
            'content' => 'Dictionary entry is not imported yet.',
        ];
    }

    wp_send_json_success($entry);
}

/**
 * Scan ZIP/SQLite3 and return a preview.
 *
 * @return array<string, mixed>
 */
function bd_reader_scan_module(string $path): array
{
    $format = preg_match('/\.zip$/i', $path) ? 'BibleQuote ZIP' : 'SQLite3';
    $name = pathinfo($path, PATHINFO_FILENAME);
    $language = bd_reader_guess_language($name);
    $books = 0;
    $verses = 0;
    $hasStrong = false;

    if ($format === 'BibleQuote ZIP' && class_exists('ZipArchive')) {
        $zip = new ZipArchive();

        if ($zip->open($path) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);

                if (! preg_match('/\.(?:htm|html|txt)$/i', $entry)) {
                    continue;
                }

                $content = (string) $zip->getFromIndex($i);
                $books++;
                $verses += bd_reader_count_verses($content);
                $hasStrong = $hasStrong || preg_match('/\b(?:[GH]\d{1,5}|\d{1,5})\b/u', $content) === 1;
            }

            $zip->close();
        }
    }

    return [
        'path' => $path,
        'file' => basename($path),
        'format' => $format,
        'name' => $name,
        'language_code' => $language,
        'books' => $books,
        'verses' => $verses,
        'has_strong' => $hasStrong,
    ];
}

/**
 * Import a module file. ZIP import is implemented first; SQLite3 preview is reserved.
 *
 * @return array<string, mixed>
 */
function bd_reader_import_module(string $path): array
{
    $preview = bd_reader_scan_module($path);

    if ($preview['format'] !== 'BibleQuote ZIP') {
        $preview['message'] = 'SQLite3 preview is available; importer will be added after table variants are mapped.';

        return $preview;
    }

    if (! class_exists('ZipArchive')) {
        wp_die('PHP ZipArchive extension is required.');
    }

    global $wpdb;

    $now = current_time('mysql');
    $moduleCode = sanitize_title($preview['name']).'-'.substr(md5((string) $path), 0, 8);

    $wpdb->replace($wpdb->prefix.'bd_modules', [
        'code' => $moduleCode,
        'name' => (string) $preview['name'],
        'short_name' => (string) $preview['name'],
        'language_code' => (string) $preview['language_code'],
        'description' => '',
        'is_active' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $moduleId = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}bd_modules WHERE code = %s", $moduleCode));

    $zip = new ZipArchive();
    $zip->open($path);
    $importedBooks = 0;
    $importedVerses = 0;

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entry = $zip->getNameIndex($i);

        if (! preg_match('/\.(?:htm|html|txt)$/i', $entry)) {
            continue;
        }

        $book = bd_reader_book_from_entry($entry, $importedBooks + 1);
        $content = (string) $zip->getFromIndex($i);
        $verses = bd_reader_extract_verses($content);

        if ($verses === []) {
            continue;
        }

        $wpdb->replace($wpdb->prefix.'bd_books', [
            'module_id' => $moduleId,
            'slug' => $book['slug'],
            'name' => $book['name'],
            'short_name' => $book['short_name'],
            'book_order' => $book['order'],
            'chapters_count' => max(array_column($verses, 'chapter')),
        ]);
        $bookId = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}bd_books WHERE module_id = %d AND slug = %s",
            $moduleId,
            $book['slug']
        ));

        foreach ($verses as $verse) {
            $text = bd_reader_clean_import_text($verse['text']);
            $wpdb->replace($wpdb->prefix.'bd_verses', [
                'module_id' => $moduleId,
                'book_id' => $bookId,
                'chapter_number' => (int) $verse['chapter'],
                'verse_number' => (int) $verse['verse'],
                'text' => $text,
                'text_plain' => bd_reader_plain_text($text),
            ]);
            $importedVerses++;
        }

        $importedBooks++;
    }

    $zip->close();

    $preview['books'] = $importedBooks;
    $preview['verses'] = $importedVerses;
    $preview['message'] = 'Imported.';

    return $preview;
}

/**
 * Extract simple chapter/verse records from BibleQuote-like HTML/TXT.
 *
 * @return list<array{chapter: int, verse: int, text: string}>
 */
function bd_reader_extract_verses(string $content): array
{
    $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $content = preg_replace('/<\s*br\s*\/?>/iu', "\n", $content) ?? $content;
    $content = preg_replace('/<\s*\/p\s*>/iu', "\n", $content) ?? $content;
    $content = strip_tags($content, '<font>');
    $lines = preg_split('/\R/u', $content) ?: [];
    $chapter = 1;
    $verses = [];

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        if (preg_match('/^(?:chapter|глава|kapitel)\s+(\d{1,3})/iu', $line, $match)) {
            $chapter = (int) $match[1];
            continue;
        }

        if (preg_match('/^(\d{1,3})\s+(.+)$/u', $line, $match)) {
            $verses[] = [
                'chapter' => $chapter,
                'verse' => (int) $match[1],
                'text' => $match[2],
            ];
        }
    }

    return $verses;
}

/**
 * Count probable verse lines for admin preview.
 */
function bd_reader_count_verses(string $content): int
{
    return count(bd_reader_extract_verses($content));
}

/**
 * Convert imported raw verse to safe stored text while preserving Strong numbers.
 */
function bd_reader_clean_import_text(string $text): string
{
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/<\s*font\b[^>]*color\s*=\s*["\']?darkred["\']?[^>]*>/iu', '<span class="bd-red">', $text) ?? $text;
    $text = preg_replace('/<\s*\/\s*font\s*>/iu', '</span>', $text) ?? $text;
    $text = strip_tags($text, '<span>');
    $text = preg_replace('/\s+([,.;:!?»])/u', '$1', $text) ?? $text;

    return preg_replace('/\s{2,}/u', ' ', trim($text)) ?? trim($text);
}

/**
 * Build plain searchable text without Strong numbers or HTML.
 */
function bd_reader_plain_text(string $text): string
{
    $text = preg_replace('/\s*\b(?:[GH]\d{1,5}|\d{1,5})\b/u', '', $text) ?? $text;
    $text = wp_strip_all_tags($text);

    return preg_replace('/\s{2,}/u', ' ', trim($text)) ?? trim($text);
}

/**
 * Render verse text with clickable Strong numbers hidden by default.
 */
function bd_reader_render_verse_text(string $text): string
{
    $safe = wp_kses($text, [
        'span' => ['class' => true],
    ]);

    return preg_replace_callback('/\b(?:[GH]\d{1,5}|\d{1,5})\b/u', static function (array $match): string {
        $number = esc_attr($match[0]);

        return '<button type="button" class="bd-strong-number" data-strong="'.$number.'">'.$number.'</button>';
    }, $safe) ?? $safe;
}

/**
 * Infer book metadata from a module entry path.
 *
 * @return array{slug: string, name: string, short_name: string, order: int}
 */
function bd_reader_book_from_entry(string $entry, int $order): array
{
    $base = pathinfo($entry, PATHINFO_FILENAME);
    $base = preg_replace('/^\d+[_\-\s]*/', '', $base) ?: $base;
    $name = trim(str_replace(['_', '-'], ' ', $base));

    return [
        'slug' => sanitize_title($base ?: 'book-'.$order),
        'name' => $name ?: 'Book '.$order,
        'short_name' => $name ?: 'Book '.$order,
        'order' => $order,
    ];
}

/**
 * Guess language from file/module naming.
 */
function bd_reader_guess_language(string $name): string
{
    $value = mb_strtolower($name);

    return match (true) {
        str_contains($value, 'ukrain') || str_contains($value, 'укра') => 'uk',
        str_contains($value, 'german') || str_contains($value, 'deutsch') || str_contains($value, 'elberfelder') => 'de',
        str_contains($value, 'english') || str_contains($value, 'kjv') || str_contains($value, 'nasb') => 'en',
        str_contains($value, 'poland') || str_contains($value, 'polish') || str_contains($value, 'gda') => 'pl',
        str_contains($value, 'russian') || str_contains($value, 'синод') || str_contains($value, 'рус') => 'ru',
        default => 'ru',
    };
}

/**
 * Basic nonce gate for public AJAX reads.
 */
function bd_reader_verify_ajax(): void
{
    $nonce = (string) ($_GET['nonce'] ?? $_POST['nonce'] ?? '');

    if (! wp_verify_nonce($nonce, 'bd_reader_ajax')) {
        wp_send_json_error(['message' => 'Bad nonce'], 403);
    }
}

add_action('admin_head', static function (): void {
    ?>
    <style>
        .bd-reader-admin-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; margin-top: 18px; }
        .bd-reader-admin-card { border: 1px solid #dcdcde; background: #fff; padding: 14px; }
        .bd-reader-admin-card img, .bd-reader-admin-cover { width: 100%; aspect-ratio: 3 / 4; object-fit: cover; background: #123f68; color: #fff; display: grid; place-items: center; font-weight: 700; }
    </style>
    <?php
});
