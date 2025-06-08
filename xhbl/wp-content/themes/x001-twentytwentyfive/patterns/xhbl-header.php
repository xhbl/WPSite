<?php
/**
 * Title: xhbl_Header
 * Slug: xhbl/xhbl_Header
 * Categories: header
 * Block Types: core/template-part/header
 */

function xhbl_header_get_navigation($nav_name, $dftnav) {
    global $wpdb;
    $nav_id = $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'wp_navigation' AND post_status = 'publish' LIMIT 1",
        $nav_name
    ));
    if ($nav_id) return (int) $nav_id;

    $nav_data = array(
        'post_title'   => $nav_name,
        'post_name'    => $nav_name,
        'post_status'  => 'publish',
        'post_type'    => 'wp_navigation',
        'post_content' => wp_slash($dftnav),
    );
    return wp_insert_post($nav_data);
}

$xtheme_uri = esc_url( get_stylesheet_directory_uri() );
$xsite_url  = esc_url( home_url() );

$navicon_content = <<<EOT
<!-- wp:navigation-link {"label":"\u003cimg class=\u0022xhbl-micon-search\u0022 style=\u0022width: 24px;\u0022 src=\u0022{$xtheme_uri}/assets/images/xhbl_micon_search.png\u0022 alt=\u0022Search\u0022\u003e","url":"{$xsite_url}/search/","kind":"post-type","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"\u003cimg class=\u0022xhbl-micon-login\u0022 style=\u0022width: 24px;\u0022 src=\u0022{$xtheme_uri}/assets/images/xhbl_micon_login.png\u0022 alt=\u0022Login\u0022\u003e","url":"{$xsite_url}/login/","kind":"post-type","isTopLevelLink":true} /-->
EOT;
$navmenu_content = <<<EOT
<!-- wp:navigation-link {"label":"导航栏一","url":"{$xsite_url}/nav1/","kind":"post-type"} /-->

<!-- wp:navigation-link {"label":"导航栏二","url":"{$xsite_url}/nav2/","kind":"post-type"} /-->

<!-- wp:navigation-link {"label":"导航栏三","url":"{$xsite_url}/nav3/","kind":"post-type"} /-->

<!-- wp:navigation-link {"label":"关于本站","url":"{$xsite_url}/about/","kind":"post-type"} /-->
EOT;

$nav_id = xhbl_header_get_navigation('xhbl_navicon', $navicon_content);
$navicon_ref = $nav_id ? '"ref":' . $nav_id : "";
$nav_id = xhbl_header_get_navigation('xhbl_navmenu', $navmenu_content);
$navmenu_ref = $nav_id ? '"ref":' . $nav_id : "";

?>
<!-- wp:group {"style":{"spacing":{"blockGap":"0px","padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}},"layout":{"inherit":true,"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px"><!-- wp:group {"align":"full","style":{"spacing":{"blockGap":"0px","padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group alignfull" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px"><!-- wp:cover {"url":"<?php echo $xtheme_uri; ?>/assets/images/xhbl_header_banner.jpg","dimRatio":10,"minHeight":100,"minHeightUnit":"px","contentPosition":"center center","isDark":false,"align":"center","style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}},"typography":{"fontSize":"0px"}}} -->
<div class="wp-block-cover aligncenter is-light" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px;font-size:0px;min-height:100px"><img class="wp-block-cover__image-background " alt="" src="<?php echo $xtheme_uri; ?>/assets/images/xhbl_header_banner.jpg" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-10 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:group {"style":{"spacing":{"blockGap":"0px"}},"layout":{"type":"constrained","contentSize":"1024px"}} -->
<div class="wp-block-group"><!-- wp:columns {"isStackedOnMobile":false,"style":{"spacing":{"padding":{"right":"10px","left":"10px","top":"8px","bottom":"4px"},"margin":{"bottom":"0"},"blockGap":{"top":"0"}}}} -->
<div class="wp-block-columns is-not-stacked-on-mobile" style="margin-bottom:0;padding-top:8px;padding-right:10px;padding-bottom:4px;padding-left:10px"><!-- wp:column {"verticalAlignment":"center","width":"80%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:80%"></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"20%","style":{"spacing":{"padding":{"right":"12px","left":"12px"}}}} -->
<div class="wp-block-column is-vertically-aligned-center" style="padding-right:12px;padding-left:12px;flex-basis:20%"><!-- wp:navigation {<?php echo $navicon_ref; ?>,"overlayMenu":"never","style":{"spacing":{"blockGap":"20px"}},"layout":{"type":"flex","justifyContent":"right","flexWrap":"nowrap"}} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:group {"style":{"dimensions":{"minHeight":"0px"}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"center"}} -->
<div class="wp-block-group" style="min-height:0px"><!-- wp:image {"width":"360px","height":"60px","sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large is-resized"><img src="<?php echo $xtheme_uri; ?>/assets/images/xhbl_header_title.png" alt="" style="width:360px;height:60px"/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->

<!-- wp:spacer {"height":"24px"} -->
<div style="height:24px" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"is-style-section-4","style":{"spacing":{"padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"},"blockGap":"0px"}},"layout":{"type":"constrained","contentSize":"1024px"}} -->
<div class="wp-block-group alignfull is-style-section-4" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px"><!-- wp:columns {"verticalAlignment":"center","isStackedOnMobile":false,"style":{"spacing":{"padding":{"top":"2px","right":"10px","bottom":"2px","left":"10px"},"blockGap":{"top":"0","left":"0"}}}} -->
<div class="wp-block-columns are-vertically-aligned-center is-not-stacked-on-mobile" style="padding-top:2px;padding-right:10px;padding-bottom:2px;padding-left:10px"><!-- wp:column {"verticalAlignment":"center","width":"104px"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:104px"><!-- wp:site-logo {"width":25,"shouldSyncIcon":false} /--></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"900px","style":{"spacing":{"padding":{"top":"5px","right":"5px","bottom":"5px","left":"5px"}}}} -->
<div class="wp-block-column is-vertically-aligned-center" style="padding-top:5px;padding-right:5px;padding-bottom:5px;padding-left:5px;flex-basis:900px"><!-- wp:navigation {<?php echo $navmenu_ref; ?>,"icon":"menu","overlayBackgroundColor":"accent-3","overlayTextColor":"accent-2","align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|50"}},"layout":{"type":"flex","justifyContent":"right","orientation":"horizontal","flexWrap":"nowrap"}} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->