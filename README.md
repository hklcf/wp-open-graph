# WP Open Graph

Open Graph and Twitter Card meta tags for WordPress, so shared links
show a proper title, description and image instead of a bare URL.

## What it outputs

On every public page, in `<head>`:

- `og:site_name`, `og:locale`, `og:url`, `og:type`, `og:title`,
  `og:description`, `og:image` (with width, height and alt when known)
- `article:published_time`, `article:modified_time`,
  `article:section` and `article:tag` on single posts
- `twitter:card` (`summary_large_image` with an image, `summary`
  without), `twitter:title`, `twitter:description`, `twitter:image`

Titles and descriptions come from the post title and excerpt, the
home page from the site name and tagline, and category/tag pages from
the term name and description. Descriptions are plain text, capped at
200 characters and cut at a sentence break so they never end
mid-word.

## Image fallback order

1. Featured image (full size)
2. First image in the content
3. Default image from **Settings > Open Graph**
4. Site icon

Set the default image if some pages have no image of their own. A
wide image (at least 1200 × 630) makes the best share preview.

## Installation

1. In WordPress admin, go to Plugins > Add New > Upload Plugin and
   choose the release zip (`wp-open-graph-1.0.1.zip`).
   Or install it by hand: the repository root is the plugin folder,
   so copy its contents into a new
   `wp-content/plugins/wp-open-graph/` folder — there is no subfolder
   to copy out of it.
2. Activate **WP Open Graph** in WordPress admin.
3. Optional: open **Settings > Open Graph** and set a default image.

## Notes

- There is no settings page beyond the default image. Everything
  else is automatic.
- To switch the tags off entirely (for example when another SEO
  plugin already outputs them), add
  `add_filter( 'wpog_enabled', '__return_false' );` to your theme.
  Do not run two Open Graph plugins at once — shared links will
  show duplicate tags.
- Tags are not output on admin pages, feeds, search results or
  404 pages.

## Version history

See `CHANGELOG.md` for the full version history.
