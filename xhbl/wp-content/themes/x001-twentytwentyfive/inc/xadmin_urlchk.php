<?php
/**
 * urlpath_checker module in admin pannel.
 */


require_once __DIR__ . '/xcommon.php';


// Add admin menu under Tools for checking URL form used in post content
add_action('admin_menu', function () {
    add_submenu_page( 'tools.php', __('Absolute/Relative URL Checker', X_TD), __('URL Checker', X_TD), 'manage_options', 'urlpath_checker', 'urlpath_check_main_ui' );
    add_submenu_page( 'urlpath_checker', __('Check Absolute URLs', X_TD), __('Absolute URLs', X_TD), 'manage_options', 'urlpath_check_absolute', 'urlpath_check_absolute_ui');
    add_submenu_page( 'urlpath_checker', __('Check Relative URLs', X_TD), __('Relative URLs', X_TD), 'manage_options', 'urlpath_check_relative', 'urlpath_check_relative_ui');
    add_submenu_page( 'urlpath_checker', __('Check GUIDs', X_TD), 'GUIDs', 'manage_options', 'urlpath_check_guid', 'urlpath_check_guid_ui');
});

// Main path checker UI - shows overview and navigation
function urlpath_check_main_ui() {
    echo "<div class='wrap'>";
    echo "<h1>" . __('Absolute/Relative URL Checker', X_TD) . "</h1>";

    // Add navigation tabs
    echo "<h2 class='nav-tab-wrapper'>";
    echo "<a href='" . admin_url('admin.php?page=urlpath_checker') . "' class='nav-tab nav-tab-active'>" . __('Overview', X_TD) . "</a>";
    echo "<a href='" . admin_url('admin.php?page=urlpath_check_absolute') . "' class='nav-tab'>" . __('Absolute URLs', X_TD) . "</a>";
    echo "<a href='" . admin_url('admin.php?page=urlpath_check_relative') . "' class='nav-tab'>" . __('Relative URLs', X_TD) . "</a>";
    echo "<a href='" . admin_url('admin.php?page=urlpath_check_guid') . "' class='nav-tab'>" . 'GUIDs' . "</a>";
    echo "</h2>";

    echo "<p>" . __('Tools for checking and converting URL paths in post_content.', X_TD) . "</p>";

    echo "<div class='card-container' style='display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px;'>";

    // Absolute URLs Card
    echo "<div class='card' style='flex: 1; padding: 20px; background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04);'>";
    echo "<h2 style='margin-top: 0;'>" . __('Absolute URLs', X_TD) . "</h2>";
    echo "<p>" . __('Check and convert absolute URLs used to reference site resources in post_content.', X_TD) . "</p>";
    echo "<p><strong>" . __('Example:', X_TD) . "</strong></p>";
    echo "<code>href=\"" . home_url() . "/sample-page\"</code><br>";
    echo "<span style='color: #888;'>↓ " . __('converts to', X_TD) . " ↓</span><br>";
    echo "<code>href=\"/sample-page\"</code>";
    echo "<p style='margin-top: 15px;'>";
    echo "<a href='" . admin_url('admin.php?page=urlpath_check_absolute') . "' class='button button-primary'>" . __('Check Absolute URLs', X_TD) . "</a>";
    echo "</p>";
    echo "</div>";

    // Relative URLs Card
    echo "<div class='card' style='flex: 1; padding: 20px; background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04);'>";
    echo "<h2 style='margin-top: 0;'>" . __('Relative URLs', X_TD) . "</h2>";
    echo "<p>" . __('Check and restore relative URLs used to reference site resources in post_content.', X_TD) . "</p>";
    echo "<p><strong>" . __('Example:', X_TD) . "</strong></p>";
    echo "<code>href=\"/sample-page\"</code><br>";
    echo "<span style='color: #888;'>↓ " . __('converts to', X_TD) . " ↓</span><br>";
    echo "<code>href=\"" . home_url() . "/sample-page\"</code>";
    echo "<p style='margin-top: 15px;'>";
    echo "<a href='" . admin_url('admin.php?page=urlpath_check_relative') . "' class='button button-primary'>" . __('Check Relative URLs', X_TD) . "</a>";
    echo "</p>";
    echo "</div>";

    // GUIDs Card
    echo "<div class='card' style='flex: 1; padding: 20px; background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04);'>";
    echo "<h2 style='margin-top: 0;'>" . 'GUIDs' . "</h2>";
    echo "<p>" . __('Check and fix post GUIDs to match current site domain.', X_TD) . "</p>";
    echo "<p><strong>" . __('Example:', X_TD) . "</strong></p>";
    echo "<code>guid=\"https://www.foo.com/?p=1\"</code><br>";
    echo "<span style='color: #888;'>↓ " . __('converts to', X_TD) . " ↓</span><br>";
    echo "<code>guid=\"" . home_url() . "/?p=1\"</code>";
    echo "<p style='margin-top: 15px;'>";
    echo "<a href='" . admin_url('admin.php?page=urlpath_check_guid') . "' class='button button-primary'>" . __('Check GUIDs', X_TD) . "</a>";
    echo "</p>";
    echo "</div>";

    echo "</div>"; // End card-container

    echo "</div>";
}

// Check for absolute to relative URL converting
function urlpath_check_absolute_ui() {
    $site_url = home_url();
    echo "<div class='wrap'><h1>" . __('Check Absolute URLs', X_TD) . "</h1>";

    // Add navigation tabs
    echo "<h2 class='nav-tab-wrapper'>";
    echo "<a href='" . admin_url('admin.php?page=urlpath_checker') . "' class='nav-tab'>" . __('Overview', X_TD) . "</a>";
    echo "<a href='" . admin_url('admin.php?page=urlpath_check_absolute') . "' class='nav-tab nav-tab-active'>" . __('Absolute URLs', X_TD) . "</a>";
    echo "<a href='" . admin_url('admin.php?page=urlpath_check_relative') . "' class='nav-tab'>" . __('Relative URLs', X_TD) . "</a>";
    echo "<a href='" . admin_url('admin.php?page=urlpath_check_guid') . "' class='nav-tab'>" . 'GUIDs' . "</a>";
    echo "</h2>";

    echo "<p>" . __('Check and convert absolute URLs used to reference site resources in post_content.', X_TD) . "</p>";

    global $wpdb;

    // Enhanced search patterns for absolute URLs
    $absolute_patterns = array(
        $site_url . "/",
        "href=\"" . $site_url,
        "src=\"" . $site_url,
        "href='" . $site_url,
        "src='" . $site_url,
        "url(" . $site_url,
        "\\u0022" . $site_url,  // JSON escaped quotes
        "\"url\":\"" . $site_url,  // JSON url fields
        "\"src\":\"" . $site_url   // JSON src fields
    );

    $where_conditions = array();
    foreach ($absolute_patterns as $pattern) {
        $where_conditions[] = "post_content LIKE '%" . esc_sql($pattern) . "%'";
    }

    $where_clause = "(" . implode(" OR ", $where_conditions) . ")";
    $posts = $wpdb->get_results("SELECT ID, post_title, post_type, post_content FROM {$wpdb->posts} WHERE {$where_clause} AND post_status = 'publish'");

    // Filter posts to only include those with actual absolute URLs to this site
    $filtered_posts = array();
    foreach ($posts as $post) {
        if (has_absolute_urls($post->post_content, $site_url)) {
            $filtered_posts[] = $post;
        }
    }

    if ($filtered_posts) {
        echo "<form method='post' action='" . admin_url('admin-post.php') . "' onsubmit=\"return confirm('" . esc_js(__('Confirm to convert all absolute URLs to relative URLs?', X_TD)) . "');\">";
        echo "<input type='hidden' name='action' value='convert_absolute_urls'>";
        echo "<p>" . __('Note: Absolute/Relative URL convertion is based on home_url', X_TD) . ": <a href='" . $site_url . "' target='_blank'>{$site_url}</a></p>";
        submit_button(__('Convert All to Relative URLs', X_TD));
        echo "</form>";
        echo "<p>" . sprintf(__('Found %d posts with absolute URLs. ', X_TD), count($filtered_posts)) . "</p>";
        echo "<ul>";
        foreach ($filtered_posts as $post) {
            echo "<li><strong>{$post->post_type}:</strong> <a href='" . get_edit_post_link($post->ID) . "' target='_blank'>{$post->post_title}</a></li>";
        }
        echo "</ul>";

        // Show preview of what will be changed
        echo "<h3>" . __('Preview matches found', X_TD) . "</h3>";
        echo "<div style='background:#f9f9f9; padding:10px; border:1px solid #ddd; max-height:400px; overflow-y:auto;'>";
        foreach ($filtered_posts as $post) {
            $preview_content = get_absolute_url_preview($post->post_content, $site_url);
            if ($preview_content) {
                echo "<strong>{$post->post_title}:</strong><br>";
                echo "<small>" . htmlspecialchars($preview_content) . "</small><br><br>";
            }
        }
        echo "</div>";

    } else {
        echo "<p><span style='color:green;'>✅ " . __('No absolute URLs found.', X_TD) . "</span></p>";
    }

    if (isset($_GET['converted']) && $_GET['converted'] == '1') {
        echo "<div class='notice notice-success'><p>" . __('All absolute URLs in post_content were successfully converted to relative URLs!', X_TD) . "</p></div>";
    }
    echo "</div>";
}

// Helper function to get preview of absolute URLs that will be changed
function get_absolute_url_preview($content, $site_url) {
    $escaped_site_url = preg_quote($site_url, '/');

    $patterns = array(
        '/href=["\']' . $escaped_site_url . '\/([^"\']*)["\']/' => 'href pattern',
        '/src=["\']' . $escaped_site_url . '\/([^"\']*)["\']/' => 'src pattern',
        '/url\(["\']?' . $escaped_site_url . '\/([^"\']*)["\']?\)/' => 'css url pattern',
        '/src=\\\\u0022' . $escaped_site_url . '\/([^\\\\]*?)\\\\u0022/' => 'json src pattern',
        '/href=\\\\u0022' . $escaped_site_url . '\/([^\\\\]*?)\\\\u0022/' => 'json href pattern',
        '/"url"\s*:\s*"' . $escaped_site_url . '\/([^"]*)"/' => 'json url field',
        '/"src"\s*:\s*"' . $escaped_site_url . '\/([^"]*)"/' => 'json src field'
    );

    $matches = array();
    foreach ($patterns as $pattern => $name) {
        preg_match_all($pattern, $content, $pattern_matches);
        if (!empty($pattern_matches[0])) {
            $matches = array_merge($matches, array_slice($pattern_matches[0], 0, 5)); // Get first 5 matches per pattern
        }
    }

    $result = '';
    if (!empty($matches)) {
        $result = implode(', ', $matches);
    }

    return $result;
}

// Check for relative to absolute URL restoration
function urlpath_check_relative_ui() {
    $site_url = home_url();
    echo "<div class='wrap'><h1>" . __('Check Relative URLs', X_TD) . "</h1>";

    // Add navigation tabs
    echo "<h2 class='nav-tab-wrapper'>";
    echo "<a href='" . admin_url('admin.php?page=urlpath_checker') . "' class='nav-tab'>" . __('Overview', X_TD) . "</a>";
    echo "<a href='" . admin_url('admin.php?page=urlpath_check_absolute') . "' class='nav-tab'>" . __('Absolute URLs', X_TD) . "</a>";
    echo "<a href='" . admin_url('admin.php?page=urlpath_check_relative') . "' class='nav-tab nav-tab-active'>" . __('Relative URLs', X_TD) . "</a>";
    echo "<a href='" . admin_url('admin.php?page=urlpath_check_guid') . "' class='nav-tab'>" . 'GUIDs' . "</a>";
    echo "</h2>";

    echo "<p>" . __('Check and restore relative URLs used to reference site resources in post_content.', X_TD) . "</p>";

    global $wpdb;

    // Look for posts that might have relative URLs (containing href="/" or src="/" patterns)
    $relative_patterns = array(
        "href=\"/",
        "src=\"/",
        "href='/",
        "src='/",
        "url(/",
        "\\u0022/",  // JSON escaped quotes with relative paths
        "\"url\":\"/",  // JSON url fields like "url":"/path/"
        "\"src\":\"/",   // JSON src fields
        ":\"/"      // Generic JSON field with relative path
    );

    $where_conditions = array();
    foreach ($relative_patterns as $pattern) {
        $where_conditions[] = "post_content LIKE '%" . esc_sql($pattern) . "%'";
    }

    $where_clause = "(" . implode(" OR ", $where_conditions) . ")";
    $posts = $wpdb->get_results("SELECT ID, post_title, post_type, post_content FROM {$wpdb->posts} WHERE {$where_clause} AND post_status = 'publish'");

    // Filter posts to only include those with actual relative URLs to this site
    $filtered_posts = array();
    foreach ($posts as $post) {
        if (has_relative_urls($post->post_content)) {
            $filtered_posts[] = $post;
        }
    }

    if ($filtered_posts) {
        echo "<form method='post' action='" . admin_url('admin-post.php') . "' onsubmit=\"return confirm('" . esc_js(__('Confirm to restore all relative URLs to absolute URLs?', X_TD)) . "');\">";
        echo "<input type='hidden' name='action' value='restore_relative_urls'>";
        echo "<p>" . __('Note: Absolute/Relative URL convertion is based on home_url', X_TD) . ": <a href='" . $site_url . "' target='_blank'>{$site_url}</a></p>";
        submit_button(__('Restore All to Absolute URLs', X_TD), 'secondary');
        echo "</form>";
        echo "<p>" . sprintf(__('Found %d posts with relative URLs.', X_TD), count($filtered_posts)) . "</p>";
        echo "<ul>";
        foreach ($filtered_posts as $post) {
            echo "<li><strong>{$post->post_type}:</strong> <a href='" . get_edit_post_link($post->ID) . "' target='_blank'>{$post->post_title}</a></li>";
        }
        echo "</ul>";

        // Show preview of what will be changed - Show ALL posts
        echo "<h3>" . __('Preview matches found', X_TD) . "</h3>";
        echo "<div style='background:#f9f9f9; padding:10px; border:1px solid #ddd; max-height:400px; overflow-y:auto;'>";
        foreach ($filtered_posts as $post) { // Show ALL posts instead of limiting to 3
            $preview_content = get_relative_url_preview($post->post_content, $site_url);
            if ($preview_content) {
                echo "<strong>{$post->post_title}:</strong><br>";
                echo "<small>" . htmlspecialchars($preview_content) . "</small><br><br>";
            }
        }
        echo "</div>";

    } else {
        echo "<p><span style='color:green;'>✅ " . __('No relative URLs found.', X_TD) . "</span></p>";
    }

    if (isset($_GET['restored']) && $_GET['restored'] == '1') {
        echo "<div class='notice notice-success'><p>" . __('All relative URLs in post_content were successfully restored to absolute URLs!', X_TD) . "</p></div>";
    }
    echo "</div>";
}

// Helper function to get preview of relative URLs that will be changed
function get_relative_url_preview($content, $site_url) {
    $patterns = array(
        '/href=["\']\/(?!\/|http|#)([^"\']*)["\']/' => 'href pattern',
        '/src=["\']\/(?!\/|http)([^"\']*)["\']/' => 'src pattern',
        '/url\(["\']?\/(?!\/|http)([^"\']*)["\']?\)/' => 'css url pattern',
        '/src=\\\\u0022\/(?!\/|http)([^\\\\]*?)\\\\u0022/' => 'json src pattern',
        '/href=\\\\u0022\/(?!\/|http)([^\\\\]*?)\\\\u0022/' => 'json href pattern',
        '/"url"\s*:\s*"\/(?!\/|http)([^"]*)"/' => 'json url field',
        '/"src"\s*:\s*"\/(?!\/|http)([^"]*)"/' => 'json src field'
    );

    $matches = array();
    foreach ($patterns as $pattern => $name) {
        preg_match_all($pattern, $content, $pattern_matches);
        if (!empty($pattern_matches[0])) {
            $matches = array_merge($matches, array_slice($pattern_matches[0], 0, 5)); // Get first 5 matches per pattern
        }
    }

    $result = '';
    if (!empty($matches)) {
        $result = implode(', ', $matches);
    }

    return $result;
}

// Handle converting absolute URLs - Enhanced version
add_action('admin_post_convert_absolute_urls', 'convert_absolute_urls');
function convert_absolute_urls() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Permission denied', X_TD));
    }

    $site_url = home_url();
    global $wpdb;

    // Enhanced search patterns for absolute URLs
    $absolute_patterns = array(
        $site_url . "/",
        "href=\"" . $site_url,
        "src=\"" . $site_url,
        "href='" . $site_url,
        "src='" . $site_url,
        "url(" . $site_url,
        "\\u0022" . $site_url,  // JSON escaped quotes
        "\"url\":\"" . $site_url,  // JSON url fields
        "\"src\":\"" . $site_url   // JSON src fields
    );

    $where_conditions = array();
    foreach ($absolute_patterns as $pattern) {
        $where_conditions[] = "post_content LIKE '%" . esc_sql($pattern) . "%'";
    }

    $where_clause = "(" . implode(" OR ", $where_conditions) . ")";
    $posts = $wpdb->get_results("SELECT ID, post_content FROM {$wpdb->posts} WHERE {$where_clause}");

    $updated_count = 0;

    foreach ($posts as $post) {
        $new_content = convert_absolute_to_relative($post->post_content, $site_url);

        if ($new_content !== $post->post_content) {
            $wpdb->update($wpdb->posts, ['post_content' => $new_content], ['ID' => $post->ID]);
            clean_post_cache($post->ID);
            $updated_count++;
        }
    }

    wp_redirect(admin_url('admin.php?page=urlpath_check_absolute&converted=1&count=' . $updated_count));
    exit;
}

// Handle restoring relative URLs to absolute URLs (new function)
add_action('admin_post_restore_relative_urls', 'restore_relative_urls');
function restore_relative_urls() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Permission denied', X_TD));
    }

    $site_url = home_url();
    global $wpdb;

    // Get all posts that might have relative URLs
    $relative_patterns = array(
        "href=\"/",
        "src=\"/",
        "href='/",
        "src='/",
        "url(/",
        "\\u0022/",  // JSON escaped quotes with relative paths
        "\"url\":\"/",  // JSON url fields like "url":"/path/"
        "\"src\":\"/",   // JSON src fields
        ":\"/"      // Generic JSON field with relative path
    );

    $where_conditions = array();
    foreach ($relative_patterns as $pattern) {
        $where_conditions[] = "post_content LIKE '%" . esc_sql($pattern) . "%'";
    }

    $where_clause = "(" . implode(" OR ", $where_conditions) . ")";
    $posts = $wpdb->get_results("SELECT ID, post_content FROM {$wpdb->posts} WHERE {$where_clause}");

    $updated_count = 0;

    foreach ($posts as $post) {
        $new_content = convert_relative_to_absolute($post->post_content, $site_url);

        if ($new_content !== $post->post_content) {
            $wpdb->update($wpdb->posts, ['post_content' => $new_content], ['ID' => $post->ID]);
            clean_post_cache($post->ID);
            $updated_count++;
        }
    }

    wp_redirect(admin_url('admin.php?page=urlpath_check_relative&restored=1&count=' . $updated_count));
    exit;
}

// Check GUID
function urlpath_check_guid_ui() {
    global $wpdb;
    $site_url = rtrim(home_url(), '/');
    $res = urlpath_split($site_url);
    $host_url = $res['hosturl'];

    echo "<div class='wrap'><h1>" . __('Check GUIDs', X_TD) . "</h1>";

    // Add navigation tabs
    echo "<h2 class='nav-tab-wrapper'>";
    echo "<a href='" . admin_url('admin.php?page=urlpath_checker') . "' class='nav-tab'>" . __('Overview', X_TD) . "</a>";
    echo "<a href='" . admin_url('admin.php?page=urlpath_check_absolute') . "' class='nav-tab'>" . __('Absolute URLs', X_TD) . "</a>";
    echo "<a href='" . admin_url('admin.php?page=urlpath_check_relative') . "' class='nav-tab'>" . __('Relative URLs', X_TD) . "</a>";
    echo "<a href='" . admin_url('admin.php?page=urlpath_check_guid') . "' class='nav-tab nav-tab-active'>" . 'GUIDs' . "</a>";
    echo "</h2>";

    echo "<p>" . __('Check and fix post GUIDs to match current site domain.', X_TD) . "</p>";

    if (isset($_GET['guid_fixed'])) {
        echo "<div class='notice notice-success'><p>✅ " . __('All GUIDs were fixed successfully!', X_TD) . "</p></div>";
    }

    $guid_results = $wpdb->get_results("SELECT ID, post_title, guid FROM {$wpdb->posts}");
    $guid_issues = [];

    foreach ($guid_results as $row) {
        $res = urlpath_split($row->guid);
        if ($res['hosturl'] !== $host_url) {
            $guid_issues[] = [
                'ID' => $row->ID,
                'title' => $row->post_title,
                'old' => $row->guid,
                'new' => $host_url . $res['relpath'],
            ];
        }
    }

    if ($guid_issues) {
        echo "<form method='post' action='" . admin_url('admin-post.php') . "' onsubmit=\"return confirm('" . esc_js(__('Confirm to fix all GUIDs?', X_TD)) . "');\">";
        echo "<input type='hidden' name='action' value='fix_guid'>";
        submit_button(__('Fix GUIDs Now', X_TD));
        echo "</form>";
        echo "<p>" . sprintf(__('Found %d GUIDs with mismatched domains.', X_TD), count($guid_issues)) . "</p>";
        echo "<table class='widefat'><thead><tr><th>" . __('Title', X_TD) . "</th><th>" . __('Old GUID', X_TD) . "</th><th>" . __('New GUID', X_TD) . "</th></tr></thead><tbody>";
        foreach ($guid_issues as $g) {
            echo "<tr><td>{$g['title']}</td><td><code>{$g['old']}</code></td><td><code>{$g['new']}</code></td></tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p><span style='color:green;'>✅ " . __('All GUIDs match current domain.', X_TD) . "</span></p>";
    }

    echo "</div>";
}

// Fix GUID
add_action('admin_post_fix_guid', function () {
    if (!current_user_can('manage_options')) wp_die(__('Permission denied', X_TD));

    global $wpdb;
    $site_url = rtrim(home_url(), '/');
    $res = urlpath_split($site_url);
    $host_url = $res['hosturl'];

    $guid_results = $wpdb->get_results("SELECT ID, guid FROM {$wpdb->posts}");

    foreach ($guid_results as $row) {
        $res = urlpath_split($row->guid);
        if ($res['hosturl'] !== $host_url) {
            $new_guid = $host_url . $res['relpath'];
            $wpdb->update($wpdb->posts, ['guid' => esc_url_raw($new_guid)], ['ID' => $row->ID]);
        }
    }

    wp_redirect(admin_url('admin.php?page=urlpath_check_guid&guid_fixed=1'));
    exit;
});
