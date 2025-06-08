<?php
/**
 * functions and definitions used in admin pannel.
 */


// urlpath_checker module
require_once __DIR__ . '/xadmin_urlchk.php';


// 添加 Global-Styles 子菜单
add_action('admin_menu', function () {
    add_submenu_page(
        'themes.php', // Add to Appearance
        __('Global Styles Editor', X_TD),
        __('Global-Styles', X_TD),
        'manage_options',
        'edit-global-style',
        'render_global_style_editor'
    );
});
function render_global_style_editor() {
    global $wpdb;
    $theme = 'wp-global-styles-' . wp_get_theme()->get_stylesheet();

    // query global styles of current theme
    $post = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}posts WHERE post_type = 'wp_global_styles' AND post_name = %s",
            $theme
        )
    );
    $json = $post ? $post->post_content : '{}';
    // format for reading
    $json = json_encode(json_decode($json, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && current_user_can('manage_options')) {
        $new_json = stripslashes_deep($_POST['global_styles_json']);
        $new_json = json_encode(json_decode($new_json, true), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($post) {
            // update
            $wpdb->update(
                $wpdb->prefix . 'posts',
                ['post_content' => $new_json],
                ['ID' => $post->ID]
            );
            echo '<div class="updated"><p>' . __('Global styles saved.', X_TD) . '</p></div>';
            $json = json_encode(json_decode($new_json, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            // insert
        }
    }

    // Save Form
    $confirm = esc_js(__('Confirm saving?', X_TD));
    echo '<div class="wrap"><h1>' . __('Edit wp-global-styles', X_TD) . '</h1>';
    echo '<form method="post" onsubmit="return confirm(\'' . $confirm . '\');">';
    echo '<textarea name="global_styles_json" rows="30" style="width:100%;">' . esc_textarea($json) . '</textarea>';
    submit_button(__('Save'));
    echo '</form></div>';
}


// 添加 Import/Export 子菜单
add_action('admin_menu', function () {
    add_submenu_page(
        'themes.php',
        __('Import/Export Theme Data', X_TD),
        __('Import/Export', X_TD),
        'manage_options',
        'theme-import-export',
        'render_theme_import_export_page'
    );
});

// 渲染页面
function render_theme_import_export_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Import/Export Current Theme Data', X_TD); ?></h1>

        <p><?php _e("Export or Import current theme's modified data stored in database, including global styles, templates, template parts, related patterns and navigation menus.", X_TD); ?></p>

        <p>
            <a href="<?php echo esc_url(admin_url('admin-post.php?action=theme_export_data')); ?>" class="button button-primary">
                <?php _e('Export Data as JSON', X_TD); ?>
            </a>
        </p>

        <hr>

        <form method="post" enctype="multipart/form-data">
            <p>
                <label for="ct_import_file"><?php _e('Select JSON file to import:', X_TD); ?></label><br>
                <input type="file" name="ct_import_file" id="ct_import_file" accept=".json" required>
            </p>
            <?php submit_button(__('Import Data from JSON', X_TD), 'primary', 'import_ctheme_data'); ?>
        </form>
    </div>
    <?php

    if (isset($_POST['import_ctheme_data']) && !empty($_FILES['ct_import_file']['tmp_name'])) {
        theme_import_block_theme_data($_FILES['ct_import_file']['tmp_name']);
    }
}

// 注册导出动作处理器
add_action('admin_post_theme_export_data', 'theme_export_block_theme_data');

// 导出处理函数
function theme_export_block_theme_data() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Unauthorized.', X_TD));
    }

    // 获取当前主题标识
    $current_theme = wp_get_theme()->get_stylesheet();
    $data = [
        'theme_info' => [
            'name'    => $current_theme,
            'version' => wp_get_theme()->get('Version')
        ]
    ];

    global $wpdb;

    // 获取当前主题的 term_id
    $theme_term_id = $wpdb->get_var($wpdb->prepare(
        "SELECT t.term_id
         FROM {$wpdb->terms} t
         INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
         WHERE tt.taxonomy = 'wp_theme'
           AND t.slug LIKE %s",
        '%' . $wpdb->esc_like($current_theme)
    ));

    // 导出主题自定义设置 (theme_mods)
    $theme_mods = get_theme_mods();
    if ($theme_mods) {
        $data['theme_mods'] = $theme_mods;
    }

    // 导出块编辑器内容
    $post_types_to_scan = ['wp_template', 'wp_template_part'];
    $post_types_to_export_direct = ['wp_global_styles'];
    $referenced_block_ids = [];
    $referenced_navigation_ids = [];
    $all_term_ids = $theme_term_id ? [$theme_term_id] : []; // 包含所有需要导出的term_id

    foreach (array_merge($post_types_to_scan, $post_types_to_export_direct) as $type) {
        $posts = get_posts([
            'post_type'   => $type,
            'numberposts' => -1,
            'post_status' => 'any'
        ]);

        foreach ($posts as $post) {
            $is_current_theme = false;
            // 全局样式特殊处理
            if ($type === 'wp_global_styles') {
                $is_current_theme = (strpos($post->post_name, 'wp-global-styles-' . $current_theme) === 0);
            }
            // 通过term关系判断主题归属
            elseif ($theme_term_id) {
                $relationship = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*)
                     FROM {$wpdb->term_relationships}
                     WHERE object_id = %d
                       AND term_taxonomy_id IN (
                         SELECT term_taxonomy_id
                         FROM {$wpdb->term_taxonomy}
                         WHERE term_id = %d
                       )",
                    $post->ID,
                    $theme_term_id
                ));
                $is_current_theme = ($relationship > 0);
            }

            if (!$is_current_theme) continue;

            // 扫描 post_content 中的 wp:block|navigation ref
            if (in_array($type, $post_types_to_scan) && !empty($post->post_content)) {
                if (preg_match_all('/<!--\s*wp:(block|navigation)\s+({.*?})\s*\/-->/s', $post->post_content, $matches)) {
                    foreach ($matches[2] as $i => $json) {
                        $attrs = json_decode($json, true);
                        if (isset($attrs['ref']) && is_numeric($attrs['ref'])) {
                            if ($matches[1][$i] === 'block') {
                                $referenced_block_ids[] = (int)$attrs['ref'];
                            } elseif ($matches[1][$i] === 'navigation') {
                                $referenced_navigation_ids[] = (int)$attrs['ref'];
                            }
                        }
                    }
                }
            }

            // 获取关联的term_id
            $terms = $wpdb->get_results($wpdb->prepare(
                "SELECT t.term_id FROM {$wpdb->terms} t
                 INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                 INNER JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
                 WHERE tr.object_id = %d",
                $post->ID
            ));
            foreach ($terms as $term) {
                $all_term_ids[] = $term->term_id;
            }

            // 导出 post 数据
            $data[$type][] = [
                'ID'           => $post->ID,
                'post_title'   => $post->post_title,
                'post_name'    => $post->post_name,
                'post_content' => $post->post_content,
                'post_status'  => $post->post_status,
                'post_type'    => $post->post_type,
                'meta'         => get_post_meta($post->ID),
                'term_relationships' => $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->term_relationships} WHERE object_id = %d",
                    $post->ID
                ), ARRAY_A)
            ];
        }
    }

    // 导出被引用的 wp_block
    $referenced_block_ids = array_values(array_unique(array_merge($referenced_block_ids, get_xhbl_theme_block_ids())));
    $data['wp_block'] = get_export_posts_by_ids('wp_block', $referenced_block_ids);

    // 导出被引用的 wp_navigation
    $referenced_navigation_ids = array_values(array_unique(array_merge($referenced_navigation_ids, get_xhbl_theme_navigation_ids())));
    $data['wp_navigation'] = get_export_posts_by_ids('wp_navigation', $referenced_navigation_ids);

    $all_term_ids = array_unique($all_term_ids);
    if (!empty($all_term_ids)) {
        // 导出wp_terms数据
        $data['wp_terms'] = $wpdb->get_results(
            "SELECT * FROM {$wpdb->terms} WHERE term_id IN (".implode(',', $all_term_ids).")",
            ARRAY_A
        );
        // 导出wp_term_taxonomy数据
        $data['wp_term_taxonomy'] = $wpdb->get_results(
            "SELECT * FROM {$wpdb->term_taxonomy}
             WHERE term_id IN (".implode(',', $all_term_ids).")",
            ARRAY_A
        );
    } else {
        $data['wp_terms'] = [];
        $data['wp_term_taxonomy'] = [];
    }

    $filename = 'theme-export-' . $current_theme . '-' . date('Ymd-His') . '.json';

    // 文件头设置
    header('Content-Description: File Transfer');
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// 获得id列表指定类型的导出内容
function get_export_posts_by_ids($post_type, $ids) {
    global $wpdb;
    $result = [];

    if (empty($ids)) return [];

    $placeholders = implode(',', array_fill(0, count($ids), '%d'));
    $query = $wpdb->prepare(
        "SELECT * FROM {$wpdb->posts}
         WHERE post_type = %s
           AND ID IN ($placeholders)",
        array_merge([$post_type], $ids)
    );

    $posts = $wpdb->get_results($query);
    foreach ($posts as $post) {
        $item = [
            'ID'           => $post->ID,
            'post_title'   => $post->post_title,
            'post_name'    => $post->post_name,
            'post_content' => $post->post_content,
            'post_status'  => $post->post_status,
            'post_type'    => $post->post_type,
            'meta'         => get_post_meta($post->ID),
        ];
        $result[] = $item;
    }
    return $result;
}

// 获得主题相关的wp_block的id列表
function get_xhbl_theme_block_ids() {
    $theme_block_namelist = ['xhbl_postcell1', 'xhbl_postcell2'];
    global $wpdb;
    $slug_placeholders = implode(',', array_fill(0, count($theme_block_namelist), '%s'));
    $post_statuses = ['publish'];
    $status_placeholders = implode(',', array_fill(0, count($post_statuses), '%s'));
    $sql = "
        SELECT ID
        FROM {$wpdb->posts}
        WHERE post_type = 'wp_block'
          AND post_name IN ($slug_placeholders)
          AND post_status IN ($status_placeholders)
    ";
    $prepare_params = array_merge($theme_block_namelist, $post_statuses);
    $results = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_params));
    $id_list = [];
    foreach ($results as $row) {
        $id_list[] = (int) $row->ID;
    }
    return $id_list;
}

// 获得主题相关的wp_navigation的id列表
function get_xhbl_theme_navigation_ids() {
    $theme_navigation_namelist = ['xhbl_navicon', 'xhbl_navmenu'];
    global $wpdb;
    $slug_placeholders = implode(',', array_fill(0, count($theme_navigation_namelist), '%s'));
    $post_statuses = ['publish'];
    $status_placeholders = implode(',', array_fill(0, count($post_statuses), '%s'));
    $sql = "
        SELECT ID
        FROM {$wpdb->posts}
        WHERE post_type = 'wp_navigation'
          AND post_name IN ($slug_placeholders)
          AND post_status IN ($status_placeholders)
    ";
    $prepare_params = array_merge($theme_navigation_namelist, $post_statuses);
    $results = $wpdb->get_results($wpdb->prepare($sql, ...$prepare_params));
    $id_list = [];
    foreach ($results as $row) {
        $id_list[] = (int) $row->ID;
    }
    return $id_list;
}

// 导入处理函数
function theme_import_block_theme_data($json_path) {
    // 1. 读取并解析JSON数据
    $json_data = file_get_contents($json_path);
    $import_data = json_decode($json_data, true);
    if (!is_array($import_data)) {
        echo '<div class="notice notice-error"><p>' . __( 'Invalid JSON file.', X_TD ) . '</p></div>';
        return;
    }

    // 2. 验证主题信息匹配性
    $current_theme = wp_get_theme()->get_stylesheet();
    if ($import_data['theme_info']['name'] !== $current_theme) {
        echo '<div class="notice notice-error"><p>' .
        sprintf(
            __( 'Import "%s" does not match current theme "%s".', X_TD ),
            $import_data['theme_info']['name'],
            $current_theme
        ) . '</p></div>';
        return;
    }

    global $wpdb;

    // 3. 恢复主题自定义设置
    if (!empty($import_data['theme_mods'])) {
        $theme_mods = $import_data['theme_mods'];
        update_option("theme_mods_{$current_theme}", $theme_mods);
    }

    // 4a. 恢复可重用块 (wp_block)
    $block_mapping = [];
    if (!empty($import_data['wp_block'])) {
        foreach ($import_data['wp_block'] as $block) {
            // 查询是否存在同名 wp_block（只取主版本）
            $existing_id = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'wp_block' AND post_name = %s AND post_status IN ('publish', 'draft', 'private') LIMIT 1",
                $block['post_name']
            ));
            $post_data = [
                'post_title'   => $block['post_title'],
                'post_name'    => $block['post_name'],
                'post_content' => wp_slash($block['post_content']),
                'post_status'  => $block['post_status'],
                'post_type'    => 'wp_block',
                'edit_date'    => true, // 避免 revision
            ];
            // 更新或插入
            if ($existing_id) {
                $post_data['ID'] = $existing_id;
                $new_id = wp_insert_post($post_data); // 更新，不产生 revision
            } else {
                $new_id = wp_insert_post($post_data); // 插入新记录
            }
            // 更新元数据
            if (!empty($block['meta'])) {
                foreach ($block['meta'] as $meta_key => $meta_values) {
                    update_post_meta($new_id, $meta_key, maybe_unserialize($meta_values[0]));
                }
            }
            // 记录新旧ID映射
            $block_mapping[$block['ID']] = $new_id;
        }
    }
    // 4b. 恢复导航 (wp_navigation)
    $navigation_mapping = [];
    if (!empty($import_data['wp_navigation'])) {
        foreach ($import_data['wp_navigation'] as $nav) {
            $existing_id = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'wp_navigation' AND post_name = %s AND post_status IN ('publish', 'draft', 'private') LIMIT 1",
                $nav['post_name']
            ));
            $post_data = [
                'post_title'   => $nav['post_title'],
                'post_name'    => $nav['post_name'],
                'post_content' => wp_slash($nav['post_content']),
                'post_status'  => $nav['post_status'],
                'post_type'    => 'wp_navigation',
                'edit_date'    => true,
            ];
            if ($existing_id) {
                $post_data['ID'] = $existing_id;
                $new_id = wp_insert_post($post_data);
            } else {
                $new_id = wp_insert_post($post_data);
            }
            if (!empty($nav['meta'])) {
                foreach ($nav['meta'] as $meta_key => $meta_values) {
                    update_post_meta($new_id, $meta_key, maybe_unserialize($meta_values[0]));
                }
            }
            $navigation_mapping[$nav['ID']] = $new_id;
        }
    }

    // 5. 获取当前主题的 term_taxonomy_id（用于关联验证）
    $current_theme_slug = $current_theme;
    $current_theme_term = $wpdb->get_row($wpdb->prepare(
        "SELECT tt.term_taxonomy_id
         FROM {$wpdb->terms} t
         INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
         WHERE tt.taxonomy = 'wp_theme'
           AND t.slug = %s",
        $current_theme_slug
    ));
    $current_theme_tax_id = $current_theme_term ? $current_theme_term->term_taxonomy_id : 0;

    // 6. 处理模板/部件/全局样式 - 主题关联删除
    $post_types = ['wp_template', 'wp_template_part', 'wp_global_styles'];
    $post_mapping = [];

    foreach ($post_types as $type) {
        if (empty($import_data[$type])) continue;

        foreach ($import_data[$type] as $post) {
            // 检查并获取已有旧记录 ID（当前主题下同名）
            $existing_id = null;
            if ($current_theme_tax_id) {
                $existing_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT p.ID FROM {$wpdb->posts} p
                     INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                     WHERE p.post_type = %s AND p.post_name = %s AND tr.term_taxonomy_id = %d
                     LIMIT 1",
                    $type,
                    $post['post_name'],
                    $current_theme_tax_id
                ));
            }
            // 预处理post_content，更新ref id
            if (!empty($post['post_content'])) {
                // 替换 wp:block 的 ID（用于引用可重用块）
                $post['post_content'] = preg_replace_callback(
                    '/<!--\s*wp:block\s+(\{.*?\})\s*\/-->/',
                    function ($matches) use ($block_mapping) {
                        $attrs = json_decode($matches[1], true);
                        if (isset($attrs['ref']) && isset($block_mapping[$attrs['ref']])) {
                            $attrs['ref'] = $block_mapping[$attrs['ref']];
                            return '<!-- wp:block ' . json_encode($attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ' /-->';
                        }
                        return $matches[0];
                    },
                    $post['post_content']
                );
                // 替换 wp:navigation 的 ID（用于引用导航块）
                $post['post_content'] = preg_replace_callback(
                    '/<!--\s*wp:navigation\s+(\{.*?\})\s*\/-->/',
                    function ($matches) use ($navigation_mapping) {
                        $attrs = json_decode($matches[1], true);
                        if (isset($attrs['ref']) && isset($navigation_mapping[$attrs['ref']])) {
                            $attrs['ref'] = $navigation_mapping[$attrs['ref']];
                            return '<!-- wp:navigation ' . json_encode($attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ' /-->';
                        }
                        return $matches[0];
                    },
                    $post['post_content']
                );
            }
            // 如果已有旧记录，指定 ID 更新
            $post_data = [
                'post_title'   => $post['post_title'],
                'post_name'    => $post['post_name'],
                'post_content' => wp_slash($post['post_content']),
                'post_status'  => $post['post_status'],
                'post_type'    => $type,
                'edit_date'    => true, // 避免产生修订
            ];
            if ($existing_id) $post_data['ID'] = $existing_id;
            $new_id = wp_insert_post($post_data);
            // 更新元数据
            if (!empty($post['meta'])) {
                foreach ($post['meta'] as $meta_key => $meta_values) {
                    update_post_meta($new_id, $meta_key, maybe_unserialize($meta_values[0]));
                }
            }

            $post_mapping[$post['ID']] = $new_id;
        }
    }

    // 7. 恢复wp_terms数据
    $term_mapping = [];
    if (!empty($import_data['wp_terms'])) {
        foreach ($import_data['wp_terms'] as $term) {
            $existing_term = $wpdb->get_row($wpdb->prepare(
                "SELECT term_id FROM {$wpdb->terms} WHERE slug = %s",
                $term['slug']
            ));

            if ($existing_term) {
                $new_term_id = $existing_term->term_id;
            } else {
                $wpdb->insert($wpdb->terms, [
                    'name'       => $term['name'],
                    'slug'       => $term['slug'],
                    'term_group' => $term['term_group']
                ]);
                $new_term_id = $wpdb->insert_id;
            }
            $term_mapping[$term['term_id']] = $new_term_id;
        }
    }

    // 8. 恢复wp_term_taxonomy数据
    $taxonomy_mapping = [];
    if (!empty($import_data['wp_term_taxonomy'])) {
        foreach ($import_data['wp_term_taxonomy'] as $taxonomy) {
            // 检查term_id是否在映射中
            if (!isset($term_mapping[$taxonomy['term_id']])) continue;

            $existing_taxonomy = $wpdb->get_row($wpdb->prepare(
                "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy}
                 WHERE term_id = %d AND taxonomy = %s",
                $term_mapping[$taxonomy['term_id']],
                $taxonomy['taxonomy']  // 动态获取taxonomy类型
            ));

            if ($existing_taxonomy) {
                $new_taxonomy_id = $existing_taxonomy->term_taxonomy_id;
            } else {
                $wpdb->insert($wpdb->term_taxonomy, [
                    'term_id'     => $term_mapping[$taxonomy['term_id']],
                    'taxonomy'    => $taxonomy['taxonomy'],  // 动态taxonomy类型
                    'description' => $taxonomy['description'],
                    'parent'      => $taxonomy['parent'],
                    'count'       => $taxonomy['count']
                ]);
                $new_taxonomy_id = $wpdb->insert_id;
            }
            $taxonomy_mapping[$taxonomy['term_taxonomy_id']] = $new_taxonomy_id;
        }
    }

    // 9. 恢复wp_term_relationships
    foreach ($post_types as $type) {
        if (empty($import_data[$type])) continue;

        foreach ($import_data[$type] as $post) {
            if (empty($post['term_relationships'])) continue;

            // 清除现有关系
            $wpdb->delete($wpdb->term_relationships, [
                'object_id' => $post_mapping[$post['ID']]
            ]);

            // 创建新关系
            foreach ($post['term_relationships'] as $relationship) {
                $wpdb->insert($wpdb->term_relationships, [
                    'object_id'        => $post_mapping[$post['ID']],
                    'term_taxonomy_id' => $taxonomy_mapping[$relationship['term_taxonomy_id']],
                    'term_order'       => $relationship['term_order']
                ]);
            }
        }
    }

    // 10. 更新wp_term_taxonomy的count值
    if (!empty($import_data['wp_term_taxonomy'])) {
        $term_taxonomy_ids = array_unique(array_values($taxonomy_mapping));

        // 一次性获取所有需要更新的count值
        $ids_str = implode(',', array_map('intval', $term_taxonomy_ids));
        if (!empty($ids_str)) {
            // 查询统计wp_term_relationships表中的实际数量
            $count_results = $wpdb->get_results("
                SELECT term_taxonomy_id, COUNT(object_id) as cnt
                FROM $wpdb->term_relationships
                WHERE term_taxonomy_id IN ($ids_str)
                GROUP BY term_taxonomy_id
            ", OBJECT_K);

            // 批量更新wp_term_taxonomy表的count值
            foreach ($term_taxonomy_ids as $tt_id) {
                $new_count = isset($count_results[$tt_id]) ? $count_results[$tt_id]->cnt : 0;
                $wpdb->update(
                    $wpdb->term_taxonomy,
                    ['count' => $new_count],
                    ['term_taxonomy_id' => $tt_id]
                );
            }
        }
    }

    // 清理缓存
    clean_term_cache($term_mapping[$term['term_id']], 'wp_theme');
    wp_cache_flush();

    echo '<div class="notice notice-success"><p>' . __('Import completed successfully.', X_TD) . '</p></div>';
    return true;
}
