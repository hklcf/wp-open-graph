# Changelog

All notable changes to WP Open Graph are recorded here. This is the
only place the full version history is kept; `README.md` only
points here.

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
