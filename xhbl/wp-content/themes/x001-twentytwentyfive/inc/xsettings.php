<?php
/**
 * setting related functions and definitions.
 */


// Rrestrict Media Library to the current user’s own media
function xhbl_show_users_own_attachments( $query )
{
    $user_id = get_current_user_id();
    if ( $user_id && !current_user_can('manage_options') && !current_user_can('edit_others_posts') )
        $query['author'] = $user_id;
    return $query;
}
add_filter( 'ajax_query_attachments_args', 'xhbl_show_users_own_attachments', 1, 1 );


// Allow Contributors to Upload Media
function xhbl_allow_contributor_uploads() {
    $contributor = get_role('contributor');
    $contributor->add_cap('upload_files');
}
if ( current_user_can('contributor') && !current_user_can('upload_files') )
    add_action('admin_init', 'allow_contributor_uploads');


// Limit the upload size for non-manage roles.
function xhbl_filter_site_upload_size_limit( $size ) {
    // Set the upload size limit to 10 MB for users lacking the 'manage_options' capability.
    if ( ! current_user_can( 'manage_options' ) ) {
        // 5 MB.
        $size = 5 * 1024 * 1024;
    }
    return $size;
}
add_filter( 'upload_size_limit', 'xhbl_filter_site_upload_size_limit', 20 );


// Remove default upload resized pictures
function xhbl_turnoff_default_resizes( $sizes ) {
    $targets = ['thumbnail', 'medium', 'medium_large', 'large'];
    foreach($sizes as $size_index=>$size) {
        if(in_array($size, $targets)) {
            unset($sizes[$size_index]);
        }
    }
    return $sizes;
}
add_filter('intermediate_image_sizes', 'xhbl_turnoff_default_resizes');


// Disables auto-saving feature for Gutenberg Editor (set interval by 1 week)
function xhbl_block_editor_settings( $editor_settings, $post ) {
    $editor_settings['autosaveInterval'] = 604800;
    return $editor_settings;
}
add_filter( 'block_editor_settings', 'xhbl_block_editor_settings', 10, 2 );


// 设置主题缺省背景图
add_action('after_switch_theme', 'xhbl_set_global_style_background_image');
function xhbl_set_global_style_background_image() {
    $theme = wp_get_theme()->get_stylesheet();
    $bg_image_url = get_theme_file_uri('assets/images/xhbl_background.png');

    // 查找当前主题对应的 global styles post
    $post_id = null;
    $existing_data = [
        'version' => 3,
        'isGlobalStylesUserThemeJSON' => true,
        'styles'  => [],
    ];

    $posts = get_posts([
        'post_type'   => 'wp_global_styles',
        'post_status' => 'publish',
        'numberposts' => 1,
        'tax_query'   => [
            [
                'taxonomy' => 'wp_theme',
                'field'    => 'slug',
                'terms'    => $theme,
            ],
        ],
    ]);

    if ($posts) {
        $post_id = $posts[0]->ID;
        $decoded = json_decode($posts[0]->post_content, true);
        if (is_array($decoded)) {
            $existing_data = $decoded;
        }
    }

    // 如果已有背景图则跳过写入
    if (!empty($existing_data['styles']['background']['backgroundImage']['url'])) {
        return; // ✅ 已有背景图，不更新
    }

    // 确保结构存在
    if (!isset($existing_data['styles']['background'])) {
        $existing_data['styles']['background'] = [];
    }

    // 设置背景图字段
    $existing_data['styles']['background']['backgroundImage'] = [
        'url' => $bg_image_url,
    ];

    // 设置默认附加样式，仅在原来没设置时才设置
    $existing_data['styles']['background']['backgroundSize'] = $existing_data['styles']['background']['backgroundSize'] ?? 'auto';
    $existing_data['styles']['background']['backgroundAttachment'] = $existing_data['styles']['background']['backgroundAttachment'] ?? 'scroll';

    // 准备写入 post
    $post_array = [
        'post_type'    => 'wp_global_styles',
        'post_name'    => 'wp-global-styles-' . $theme,
        'post_title'   => 'Custom Styles',
        'post_status'  => 'publish',
        'post_content' => wp_json_encode($existing_data),
        'edit_date'    => true, // ✅ 防止 revision
    ];

    if ($post_id) {
        $post_array['ID'] = $post_id;
    }

    $inserted_id = wp_insert_post($post_array);

    // 新建时设置主题 term
    if (!$post_id && $inserted_id && !is_wp_error($inserted_id)) {
        wp_set_object_terms($inserted_id, $theme, 'wp_theme');
    }
}


// 设置主题缺省站点图标
add_action( 'after_switch_theme', 'xhbl_set_default_site_logo_icon' );
function xhbl_set_default_site_logo_icon() {
    if ( get_theme_mod( 'custom_logo' ) && get_option( 'site_icon' ) ) return;
    $logo_icon_rel_url = '/assets/images/xhbl_site_home.png';
    $file_path     = get_stylesheet_directory() . $logo_icon_rel_url;
    if ( ! file_exists( $file_path ) ) return;

    $file_contents = file_get_contents( $file_path );
    $filename      = basename( $file_path );
    $file_md5      = md5( $file_contents );

    // 检查是否已有相同 hash 的图片在媒体库中
    $existing_id = find_existing_media_by_md5( $file_md5 );
    if ( !$existing_id ) {
        // 上传文件
        $upload_file = wp_upload_bits( $filename, null, $file_contents );
        if ( $upload_file['error'] ) return;
        $filetype = wp_check_filetype( $upload_file['file'], null );
        $attachment = [
            'post_mime_type' => $filetype['type'],
            'post_title'     => sanitize_file_name( $filename ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];
        $existing_id = wp_insert_attachment( $attachment, $upload_file['file'] );

        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attach_data = wp_generate_attachment_metadata( $existing_id, $upload_file['file'] );
        wp_update_attachment_metadata( $existing_id, $attach_data );
        // 将 MD5 存入 attachment meta，便于下次复用
        update_post_meta( $existing_id, '_xhbl_file_md5', $file_md5 );
    }

    // 设置 custom_logo 和 site_icon（若未设置）
    if ( ! get_theme_mod( 'custom_logo' ) ) {
        set_theme_mod( 'custom_logo', $existing_id );
    }
    if ( ! get_option( 'site_icon' ) ) {
        update_option( 'site_icon', $existing_id );
    }
}

function find_existing_media_by_md5( $target_md5 ) {
    $args = [
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => -1,
        'meta_key'       => '_xhbl_file_md5',
        'meta_value'     => $target_md5,
        'fields'         => 'ids',
    ];
    $query = new WP_Query( $args );
    return ! empty( $query->posts ) ? $query->posts[0] : 0;
}
