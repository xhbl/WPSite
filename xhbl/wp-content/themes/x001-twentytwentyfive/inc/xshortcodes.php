<?php
/**
 * shortcodes functions and definitions.
 */

// login form shortcode
function xhbl_sc_login_form() {
    ob_start();
    if ( !is_user_logged_in() ) {
        $args = array(
            'form_id' => 'loginform-xhbl',
            'label_username' => __( 'Username: ', X_TD ),
            'label_password' => __( 'Password: ', X_TD ),
            'label_remember' => __( 'Remember me', X_TD ),
            'label_log_in' => __( 'Log in' ));
        wp_login_form( $args );
    } else {
        global $current_user;
        wp_get_current_user();
        echo '<p>' . __( 'Current User', X_TD ). ': <a href="' . admin_url( 'profile.php' ) . '">' . $current_user->user_login . '</a></p>';
        echo __( '<p>|</p>' );
        echo '<p><a href="' . esc_url( wp_logout_url( get_permalink() ) ) . '">' . __( 'Log out' ) . '</a></p>';
    }
    return ob_get_clean();
}

// post views number shortcode
function xhbl_sc_post_views_num() {
    $defaults = [
        'id'    => get_the_ID(),
        'type'  => 'post'
    ];
    // when Post Views Counter Plugins available
    if ( ! function_exists( 'pvc_post_views' ) ) {
        return;
    }
    // main item?
    if ( ! in_the_loop() ) {
        // get current object
        $object = get_queried_object();

        // post?
        if ( is_a( $object, 'WP_Post' ) ) {
            $defaults['id'] = $object->ID;
            $defaults['type'] = 'post';
        }
    }
    // combine attributes
    $args = shortcode_atts( $defaults, $args );
    // default type?
    if ( $args['type'] === 'post' )
        $views = pvc_post_views( $args['id'], false );
    else
        $views = '';
    // strip icon and text
    $views = strstr( $views, '<span class="post-views-icon', true ) . strstr( $views, '<span class="post-views-count' );
    return $views;
}

// Add shortcodes
function xhbl_add_shortcodes() {
    // Add shortcodes here
    add_shortcode( 'xhbl-login-form', 'xhbl_sc_login_form' );
    add_shortcode( 'post-views-num', 'xhbl_sc_post_views_num' );
}
add_action( 'init', 'xhbl_add_shortcodes' );
