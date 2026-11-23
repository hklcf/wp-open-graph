# Changelog

All notable changes to WP Open Graph are recorded here. This is the
only place the full version history is kept; `README.md` only
points here.

## 1.0.1 — 2026-11-24

- Descriptions are now capped at 200 characters (was 300) and cut
  at a sentence break, so they never end mid-word or run into the
  quick-summary table that follows the intro paragraph.
- Fixed the LICENSE stub, which still named WP Site Hardening from
  the file it was copied from.

## 1.0.0 — 2026-11-24

- First release. Outputs Open Graph and Twitter Card meta tags on
  every public page: site name, locale, URL, type, title,
  description and image (with width, height and alt when known),
  plus published/modified time, section and tags on single posts.
- Image fallback order: featured image, first image in the
  content, default image from Settings > Open Graph, site icon.
- Settings page with one field: the default image (media library
  picker included). `wpog_enabled` filter switches the tags off
  entirely.
