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
