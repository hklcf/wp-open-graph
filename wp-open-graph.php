<?php
/**
 * Plugin Name: WP Open Graph
 * Plugin URI: https://github.com/hklcf/wp-open-graph
 * Description: Open Graph and Twitter Card meta tags for posts, pages and the home page, so shared links show a proper title, description and image. Image fallback: featured image, first image in the content, default image from settings, site icon.
 * Version: 1.0.1
 * Author: HKLCF
 * Author URI: https://github.com/hklcf
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: wp-open-graph
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPOG_VERSION', '1.0.1' );
define( 'WPOG_SETTINGS_OPTION', 'wp_open_graph_settings' );

/* ------------------------------------------------------------------ */
/* Settings                                                            */
/* ------------------------------------------------------------------ */

function wpog_default_settings() {
	return array(
		'default_image' => '',
	);
}

function wpog_get_settings() {
	$settings = get_option( WPOG_SETTINGS_OPTION, array() );
	if ( ! is_array( $settings ) ) {
		$settings = array();
	}
	return array_merge( wpog_default_settings(), $settings );
}

/* ------------------------------------------------------------------ */
/* Tag values                                                          */
/* ------------------------------------------------------------------ */

// Width, height and alt text for a local media-library image URL.
// Returns zeros and an empty alt when the URL is not a local attachment.
function wpog_attachment_data( $url ) {
	$data = array(
		'width'  => 0,
		'height' => 0,
		'alt'    => '',
	);
	$attach_id = attachment_url_to_postid( $url );
	if ( ! $attach_id ) {
		return $data;
	}
	$meta = wp_get_attachment_metadata( $attach_id );
	if ( ! empty( $meta['width'] ) ) {
		$data['width']  = (int) $meta['width'];
		$data['height'] = (int) $meta['height'];
	}
	$data['alt'] = get_post_meta( $attach_id, '_wp_attachment_image_alt', true );
	return $data;
}

// The image for the current page. Fallback order: featured image,
// first image in the content, default image from settings, site icon.
function wpog_get_image() {
	$image = array(
		'url'    => '',
		'width'  => 0,
		'height' => 0,
		'alt'    => '',
	);
	if ( is_singular() ) {
		$post_id  = get_the_ID();
		$thumb_id = get_post_thumbnail_id( $post_id );
		if ( $thumb_id ) {
			$src = wp_get_attachment_image_src( $thumb_id, 'full' );
			if ( $src ) {
				$image['url']    = $src[0];
				$image['width']  = (int) $src[1];
				$image['height'] = (int) $src[2];
				$image['alt']    = get_post_meta( $thumb_id, '_wp_attachment_image_alt', true );
			}
		}
		if ( '' === $image['url'] ) {
			$content = get_post_field( 'post_content', $post_id );
			if ( preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $matches ) ) {
				$image['url'] = $matches[1];
				$data         = wpog_attachment_data( $image['url'] );
				$image        = array_merge( $image, $data );
			}
		}
		if ( '' === $image['alt'] ) {
			$image['alt'] = get_the_title( $post_id );
		}
	}
	if ( '' === $image['url'] ) {
		$settings = wpog_get_settings();
		if ( ! empty( $settings['default_image'] ) ) {
			$image['url'] = $settings['default_image'];
			$data         = wpog_attachment_data( $image['url'] );
			$image        = array_merge( $image, $data );
		}
	}
	if ( '' === $image['url'] ) {
		$icon = get_site_icon_url( 512 );
		if ( $icon ) {
			$image['url']    = $icon;
			$image['width']  = 512;
			$image['height'] = 512;
		}
	}
	return $image;
}

function wpog_get_title() {
	if ( is_singular() ) {
		return get_the_title();
	}
	if ( is_home() || is_front_page() ) {
		$name = get_bloginfo( 'name' );
		$desc = get_bloginfo( 'description' );
		return '' !== $desc ? $name . ' – ' . $desc : $name;
	}
	if ( is_category() || is_tag() || is_tax() ) {
		return single_term_title( '', false );
	}
	return wp_get_document_title();
}

function wpog_get_description() {
	if ( is_singular() ) {
		$text = get_the_excerpt();
		if ( '' === $text ) {
			$text = get_post_field( 'post_content', get_the_ID() );
		}
	} elseif ( is_home() || is_front_page() ) {
		$text = get_bloginfo( 'description' );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$text = term_description();
	} else {
		$text = get_bloginfo( 'description' );
	}
	$text = wp_strip_all_tags( (string) $text );
	$text = trim( (string) preg_replace( '/\s+/', ' ', $text ) );
	// Cap at 200 characters and cut at a sentence break so the
	// description never ends mid-word. A space only counts as a
	// break when there is no sentence punctuation (Latin text);
	// inside Chinese text spaces also appear mid-phrase, for
	// example in "¥2,500 (約 HK$130)". The break is only used
	// when it keeps at least half the text; otherwise hard-cut.
	$limit = 200;
	if ( function_exists( 'mb_strlen' ) && mb_strlen( $text ) > $limit ) {
		$cut = mb_substr( $text, 0, $limit );
		if ( preg_match( '/^(.*[。！？；：、，.!?])[^。！？；：、，.!?]*$/u', $cut, $matches ) && mb_strlen( $matches[1] ) >= $limit / 2 ) {
			$cut = rtrim( $matches[1] );
		} elseif ( preg_match( '/^(.*\s)\S*$/u', $cut, $matches ) && mb_strlen( $matches[1] ) >= $limit / 2 ) {
			$cut = rtrim( $matches[1] );
		}
		$text = $cut . '…';
	} elseif ( strlen( $text ) > $limit ) {
		$text = substr( $text, 0, $limit ) . '…';
	}
	return $text;
}

function wpog_get_url() {
	if ( is_singular() ) {
		return get_permalink();
	}
	if ( is_home() || is_front_page() ) {
		return home_url( '/' );
	}
	if ( is_category() || is_tag() || is_tax() ) {
		$link = get_term_link( get_queried_object() );
		if ( ! is_wp_error( $link ) ) {
			return $link;
		}
	}
	// Anything else (date archives, author pages, ...): the current
	// request path under the home URL.
	if ( isset( $GLOBALS['wp']->request ) && '' !== $GLOBALS['wp']->request ) {
		return home_url( $GLOBALS['wp']->request );
	}
	return home_url( '/' );
}

/* ------------------------------------------------------------------ */
/* Output                                                              */
/* ------------------------------------------------------------------ */

function wpog_output_tags() {
	if ( is_admin() || is_feed() || is_404() || is_search() ) {
		return;
	}
	// Let themes or other plugins switch the tags off entirely.
	if ( ! apply_filters( 'wpog_enabled', true ) ) {
		return;
	}
	$title = wpog_get_title();
	$desc  = wpog_get_description();
	$url   = wpog_get_url();
	$image = wpog_get_image();
	$type  = is_singular( 'post' ) ? 'article' : 'website';

	echo "\n<!-- Open Graph tags by WP Open Graph -->\n";
	echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . "\" />\n";
	echo '<meta property="og:locale" content="' . esc_attr( get_locale() ) . "\" />\n";
	if ( '' !== $url ) {
		echo '<meta property="og:url" content="' . esc_url( $url ) . "\" />\n";
	}
	echo '<meta property="og:type" content="' . esc_attr( $type ) . "\" />\n";
	if ( '' !== $title ) {
		echo '<meta property="og:title" content="' . esc_attr( $title ) . "\" />\n";
	}
	if ( '' !== $desc ) {
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . "\" />\n";
	}
	if ( '' !== $image['url'] ) {
		echo '<meta property="og:image" content="' . esc_url( $image['url'] ) . "\" />\n";
		if ( $image['width'] ) {
			echo '<meta property="og:image:width" content="' . (int) $image['width'] . "\" />\n";
		}
		if ( $image['height'] ) {
			echo '<meta property="og:image:height" content="' . (int) $image['height'] . "\" />\n";
		}
		if ( '' !== $image['alt'] ) {
			echo '<meta property="og:image:alt" content="' . esc_attr( $image['alt'] ) . "\" />\n";
		}
	}
	if ( 'article' === $type ) {
		echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( 'c' ) ) . "\" />\n";
		echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( 'c' ) ) . "\" />\n";
		$categories = get_the_category();
		if ( ! empty( $categories ) ) {
			echo '<meta property="article:section" content="' . esc_attr( $categories[0]->name ) . "\" />\n";
		}
		$tags = get_the_tags();
		if ( ! empty( $tags ) ) {
			foreach ( $tags as $tag ) {
				echo '<meta property="article:tag" content="' . esc_attr( $tag->name ) . "\" />\n";
			}
		}
	}
	// Twitter Card. X reads the Open Graph tags too, but its own tags
	// decide whether the preview is a large image or a small summary.
	$card = '' !== $image['url'] ? 'summary_large_image' : 'summary';
	echo '<meta name="twitter:card" content="' . esc_attr( $card ) . "\" />\n";
	if ( '' !== $title ) {
		echo '<meta name="twitter:title" content="' . esc_attr( $title ) . "\" />\n";
	}
	if ( '' !== $desc ) {
		echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . "\" />\n";
	}
	if ( '' !== $image['url'] ) {
		echo '<meta name="twitter:image" content="' . esc_url( $image['url'] ) . "\" />\n";
	}
}
add_action( 'wp_head', 'wpog_output_tags', 5 );

/* ------------------------------------------------------------------ */
/* Settings page                                                       */
/*                                                                     */
/* Settings > Open Graph. One field: the default image used when a
 * page has no featured image and no image in its content. The site
 * icon is the last fallback after it.
 * ------------------------------------------------------------------ */

function wpog_register_settings_page() {
	add_options_page(
		__( 'Open Graph', 'wp-open-graph' ),
		__( 'Open Graph', 'wp-open-graph' ),
		'manage_options',
		'wp-open-graph',
		'wpog_render_settings_page'
	);
}
add_action( 'admin_menu', 'wpog_register_settings_page' );

function wpog_enqueue_media_picker( $hook ) {
	if ( 'settings_page_wp-open-graph' !== $hook ) {
		return;
	}
	wp_enqueue_media();
	wp_add_inline_script(
		'media-editor',
		'jQuery(function($){$("#wpog_pick_image").on("click",function(e){e.preventDefault();var f=wp.media({title:"Choose a default Open Graph image",multiple:false,library:{type:"image"}});f.on("select",function(){var a=f.state().get("selection").first().toJSON();$("#wpog_default_image").val(a.url);});f.open();});});'
	);
}
add_action( 'admin_enqueue_scripts', 'wpog_enqueue_media_picker' );

function wpog_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-open-graph' ) );
	}
	if ( isset( $_POST['wpog_save_settings'] ) && check_admin_referer( 'wpog_settings_nonce' ) ) {
		$image = isset( $_POST['wpog_default_image'] ) ? esc_url_raw( wp_unslash( $_POST['wpog_default_image'] ) ) : '';
		update_option(
			WPOG_SETTINGS_OPTION,
			array(
				'default_image' => $image,
			)
		);
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'wp-open-graph' ) . '</p></div>';
	}
	$settings = wpog_get_settings();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Open Graph', 'wp-open-graph' ); ?></h1>
		<p><?php esc_html_e( 'Adds Open Graph and Twitter Card meta tags to the head of every page, so shared links show a proper title, description and image.', 'wp-open-graph' ); ?></p>
		<form method="post" action="">
			<?php wp_nonce_field( 'wpog_settings_nonce' ); ?>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="wpog_default_image"><?php esc_html_e( 'Default image', 'wp-open-graph' ); ?></label></th>
					<td>
						<input type="url" name="wpog_default_image" id="wpog_default_image" value="<?php echo esc_attr( $settings['default_image'] ); ?>" class="regular-text" />
						<button type="button" class="button" id="wpog_pick_image"><?php esc_html_e( 'Choose from Media Library', 'wp-open-graph' ); ?></button>
						<p class="description"><?php esc_html_e( 'Used when a page has no featured image and no image in its content. Leave empty to fall back to the site icon. A wide image (at least 1200 × 630) makes the best share preview.', 'wp-open-graph' ); ?></p>
						<?php if ( ! empty( $settings['default_image'] ) ) : ?>
							<p><img src="<?php echo esc_url( $settings['default_image'] ); ?>" alt="" style="max-width: 300px; height: auto;" /></p>
						<?php endif; ?>
					</td>
				</tr>
			</table>
			<?php submit_button( __( 'Save Settings', 'wp-open-graph' ), 'primary', 'wpog_save_settings' ); ?>
		</form>
		<h2><?php esc_html_e( 'Image fallback order', 'wp-open-graph' ); ?></h2>
		<ol>
			<li><?php esc_html_e( 'Featured image (full size)', 'wp-open-graph' ); ?></li>
			<li><?php esc_html_e( 'First image in the content', 'wp-open-graph' ); ?></li>
			<li><?php esc_html_e( 'Default image above', 'wp-open-graph' ); ?></li>
			<li><?php esc_html_e( 'Site icon', 'wp-open-graph' ); ?></li>
		</ol>
	</div>
	<?php
}
