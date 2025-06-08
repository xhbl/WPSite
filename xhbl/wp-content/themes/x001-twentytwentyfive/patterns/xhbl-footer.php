<?php
/**
 * Title: xhbl_Footer
 * Slug: xhbl/xhbl_Footer
 * Categories: footer
 * Block Types: core/template-part/footer
 */

$xsite_url  = esc_url( home_url() );

?>
<!-- wp:group {"style":{"spacing":{"blockGap":"0px","padding":{"top":"0px","right":"0px","bottom":"0px","left":"0px"}}},"layout":{"inherit":true,"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:0px;padding-right:0px;padding-bottom:0px;padding-left:0px"><!-- wp:group {"align":"full","className":"is-style-section-4","style":{"spacing":{"blockGap":"0px","padding":{"top":"15px","right":"15px","bottom":"15px","left":"15px"}}},"layout":{"inherit":false,"contentSize":"1024px","type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-section-4" style="padding-top:15px;padding-right:15px;padding-bottom:15px;padding-left:15px"><!-- wp:paragraph {"align":"center","className":"is-style-default","style":{"elements":{"link":{"color":{"text":"var:preset|color|accent-2"}}}},"textColor":"accent-2","fontSize":"medium"} -->
<p class="has-text-align-center is-style-default has-accent-2-color has-text-color has-link-color has-medium-font-size"><a href="<?php echo $xsite_url; ?>/copyright/" data-type="page" data-id="3">版权所有</a> © <?php echo wp_date('Y'); ?> <a href="<?php echo $xsite_url; ?>/about/" data-type="page" data-id="2">XHBL</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->