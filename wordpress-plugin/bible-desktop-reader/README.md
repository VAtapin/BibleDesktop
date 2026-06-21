# Bible Desktop Reader WordPress Plugin

Lightweight WordPress plugin prototype for embedding Bible Desktop reader features in a WordPress site.

## Features

- Own WordPress database tables, independent from Laravel.
- Admin menu: `Bible Desktop -> Modules` and `Bible Desktop -> Import`.
- BibleQuote ZIP preview and import.
- Frontend shortcode: `[bible_desktop]`.
- Reader controls for module, book, chapter, search, and Strong number display.
- Strong numbers are stored in verse text, hidden by default, and clickable when enabled.
- Red-letter spans from imported `<font color="darkred">` markup are preserved as safe `<span class="bd-red">`.

## Install

1. Copy `bible-desktop-reader` into `wp-content/plugins/`.
2. Activate `Bible Desktop Reader` in WordPress admin.
3. Open `Bible Desktop -> Import`, upload a BibleQuote ZIP, click `Check module`, then `Import module`.
4. Add `[bible_desktop]` to a page.

## Notes

- SQLite3 module preview is detected, but full SQLite import is intentionally left as a mapped follow-up because modules use different table schemas.
- Strong dictionary import has a table and AJAX endpoint, but dictionary import UI is a follow-up.
- Parallel places have a table placeholder; importing source formats is a follow-up.
