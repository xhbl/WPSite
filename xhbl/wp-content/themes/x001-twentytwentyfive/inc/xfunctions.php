<?php
/**
 * eXtra Header-footer and Body Layout functions and definitions.
 */


define( 'CT_DIR', get_stylesheet_directory() );
define( 'CT_URI', get_stylesheet_directory_uri() );
define( 'PT_DIR', get_template_directory() );
define( 'PT_URI', get_template_directory_uri() );


// Load extra language translation
define( 'X_TD', 'xhbl' );
add_action( 'after_setup_theme', function() {
    $domain = X_TD;
    $locale = is_admin() ? get_user_locale() : determine_locale();
    $custom_mo = CT_DIR . "/languages/xhbl-{$locale}.mo";
    if ( file_exists( $custom_mo ) ) {
        load_textdomain( $domain, $custom_mo );
    }
} );
function test_str_mo () {
	return '';
}
// add_action( 'wp_head', function() { echo '<p>Test-MO：' . test_str_mo() . '</p>'; });


// Required function files
require_once __DIR__ . '/xcommon.php';
require_once __DIR__ . '/xsettings.php';
require_once __DIR__ . '/xshortcodes.php';
require_once __DIR__ . '/xadmin.php';


// Change login logo
add_action( 'login_head', function () {
    $site_logo_id = get_theme_mod( 'custom_logo' );
    if ( ! $site_logo_id ) {
        $site_logo_id = get_option( 'site_icon' );
    }
    if ( ! $site_logo_id ) return;
    $logo_url = wp_get_attachment_url( $site_logo_id );
    if ( ! $logo_url ) return;

    $logo_meta = wp_get_attachment_metadata( $site_logo_id );
    $logo_h = $logo_w = 84;
    if ( isset($logo_meta['width'], $logo_meta['height']) && $logo_meta['height'] != 0 ) {
        $logo_w = (int) round( $logo_h * (float)$logo_meta['width'] / (float)$logo_meta['height'] );
    }

    echo '<style type="text/css">
        .login h1 a {
            background-image: url("' . esc_url( $logo_url ) . '") !important;
            height: ' . $logo_h . 'px;
            width: ' . $logo_w . 'px;
            background-size: auto 100%;
            background-position: center;
            background-repeat: no-repeat;
        }
    </style>';
});

// Change login logo link
function xhbl_login_logo_url() {
    return home_url();
}
add_filter( 'login_headerurl', 'xhbl_login_logo_url' );

function xhbl_login_logo_url_title() {
    return get_bloginfo( 'name' );
}
add_filter( 'login_headertext', 'xhbl_login_logo_url_title' );


// Comment section change
function xhbl_comment_removeurl($arg) {
    $arg['url'] = '';
    return $arg;
}
add_filter('comment_form_default_fields', 'xhbl_comment_removeurl');


// Restrict block next post and previous post to same category
function xhbl_adjacent_post_link( $output, $format, $link, $post, $adjacent )
{
    $previous = 'previous' === $adjacent;
    if ( ! ( $previous && is_attachment() ) ) {
        $post = get_adjacent_post( true, '', $previous, 'category' );
    }
    if ( ! $post ) {
        $output = '';
    } else {
        $title = $post->post_title;

        if ( empty( $post->post_title ) ) {
            $title = $previous ? __( 'Previous Post' ) : __( 'Next Post' );
        }
        $title = apply_filters( 'the_title', $title, $post->ID );
        $date = mysql2date( get_option( 'date_format' ), $post->post_date );
        $rel  = $previous ? 'prev' : 'next';
        $string = '<a href="' . get_permalink( $post ) . '" rel="' . $rel . '">';
        $inlink = str_replace( '%title', $title, $link );
        $inlink = str_replace( '%date', $date, $inlink );
        $inlink = $string . $inlink . '</a>';
        $output = str_replace( '%link', $inlink, $format );
    }
    return $output;
}
add_filter( 'next_post_link',     'xhbl_adjacent_post_link', 10, 5 );
add_filter( 'previous_post_link', 'xhbl_adjacent_post_link', 10, 5 );


// Words trimming for mixed language
function xhbl_trim_words( $text, $num_words, $more, $original_text ) {
    $text          = $original_text;
    $text          = wp_strip_all_tags( $text );
    $num_words     = (int) $num_words;
    // Count as characters
    $text = trim( preg_replace( "/[\n\r\t ]+/", ' ', $text ), ' ' );
    preg_match_all( '/./u', $text, $words_array );
    $cnt_words = 0;
    for ($c = 0; $c < count( $words_array[0] ); $c++) {
        if( mb_ord( $words_array[0][$c] ) > 255 ) $cnt_words += 2;
        else $cnt_words += 1;
        if( $cnt_words > $num_words * 2 ) {
            $cnt_words = $c + 1;
            break;
        }
    }
    $words_array = array_slice( $words_array[0], 0, $cnt_words + 1 );
    $sep         = '';
    if ( count( $words_array ) > $cnt_words ) {
        array_pop( $words_array );
        $text = implode( $sep, $words_array );
        $text = $text . $more;
    } else {
        $text = implode( $sep, $words_array );
    }
    return $text;
}
add_filter( 'wp_trim_words', 'xhbl_trim_words', 10, 4 );

// Specify Excerpt Here
function xhbl_excerpt_length( $excerpt_length ) {
    return 140;
}
add_filter( 'excerpt_length', 'xhbl_excerpt_length' );

// Use additional class in post template to customize loop query
add_filter( 'query_loop_block_query_vars', function( $query, $block ) {
    if ( ! is_admin() && ! empty( $block->parsed_block['attrs']['className'] ) ) {
        if ( 'xhbl-pt-orderby-post-views' === $block->parsed_block['attrs']['className'] && function_exists( 'pvc_post_views' ) ) {
            // order by Post Views Counter Plugins's most post views if activated
            $query['orderby'] = 'post_views';
            $query['order'] = 'DESC';
        }
    }
    return $query;
}, 10, 2 );


// Use additional class for re-render
add_filter( 'render_block', function( $block_content, $block ) {
    if ( ! is_admin() && ! empty( $block['attrs']['className'] ) ) {
        if ( function_exists( 'pvc_post_views' ) ) {
            // do shortcode (shall be used within query loop)
            if ( 'xhbl-sc-post-views' === $block['attrs']['className'] ) {
                // Do post views short code of Post Views Counter Plugins if activated
                return do_shortcode( '[post-views]' );
            }
            if ( 'xhbl-sc-post-views-num' === $block['attrs']['className'] ) {
                // Do post views num short code of Post Views Counter Plugins if activated
                return do_shortcode( '[post-views-num]' );
            }
        }
        if ( 'xhbl-hide-nopfi-column' === $block['attrs']['className'] ) {
            // remove empty featured image block
            if ( strpos( $block_content, 'wp-block-post-featured-image' ) === false ) {
                return '';
            }
        }
    }
    return $block_content;
}, 10, 2 );
