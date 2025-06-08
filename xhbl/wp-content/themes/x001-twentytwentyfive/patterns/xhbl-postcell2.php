<?php
/**
 * Title: xhbl_PostCell2
 * Slug: xhbl/xhbl_PostCell2
 * Inserter: no
 */

function xhbl_PostCell2_get_block() {
    global $wpdb;
    $block_id = $wpdb->get_var(
        "SELECT ID FROM {$wpdb->posts} WHERE post_name = 'xhbl_postcell2' AND post_type = 'wp_block' AND post_status = 'publish' LIMIT 1"
    );
    if ($block_id) return (int) $block_id;

    $block_content = <<<EOT
<!-- wp:group {"align":"wide","style":{"spacing":{"blockGap":"12px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide"><!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/9","width":"","align":"wide"} /-->

<!-- wp:group {"align":"wide","style":{"spacing":{"blockGap":"8px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide"><!-- wp:post-title {"isLink":true,"align":"wide","style":{"typography":{"fontStyle":"normal","fontWeight":"600"}}} /-->

<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"100%","style":{"spacing":{"blockGap":"10px"}}} -->
<div class="wp-block-column" style="flex-basis:100%"><!-- wp:post-excerpt {"moreText":"","excerptLength":100,"fontSize":"medium"} /-->

<!-- wp:group {"layout":{"type":"flex","allowOrientation":false,"justifyContent":"space-between"}} -->
<div class="wp-block-group"><!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:post-date {"format":"Y-m-d","style":{"typography":{"fontStyle":"italic","fontWeight":"400"}},"fontSize":"medium"} /-->

<!-- wp:post-author {"showAvatar":false,"fontSize":"medium"} /--></div>
<!-- /wp:group -->

<!-- wp:read-more {"content":"[阅读全文]","fontSize":"medium"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
EOT;

    $block_data = array(
        'post_title'   => 'xhbl_PostCell2',
        'post_name'    => 'xhbl_postcell2',
        'post_status'  => 'publish',
        'post_type'    => 'wp_block',
        'post_content' => wp_slash($block_content),
        'meta_input'   => array(
            'wp_pattern_sync_status' => 'synced')
    );
    return wp_insert_post($block_data);
}

?>
<!-- wp:block {"ref":<?php echo xhbl_PostCell2_get_block(); ?>} /-->