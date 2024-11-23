<?php
/*
Plugin Name: Unused Plugins and Themes Checker
Description: A plugin to identify and delete unused plugins and themes in a WordPress multisite network.
Version: 2.2
Author: Aaditya Uzumaki 
Website: https://goenka.xyz
*/

// Enqueue Styles and Scripts
add_action('admin_enqueue_scripts', 'uptc_enqueue_assets');
function uptc_enqueue_assets($hook_suffix) {
    if ($hook_suffix === 'toplevel_page_uptc-unused-plugins-themes') {
        // Enqueue Google Fonts
        wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Raleway:wght@400;700&family=EB+Garamond:wght@400;700&display=swap', [], null);

        // Enqueue Bootstrap CSS and DataTables
        wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
        wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css');
        
        // Enqueue Scripts
        wp_enqueue_script('jquery');
        wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', [], null, true);
        wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js', ['jquery'], null, true);

        // Inline styles for clay morphic/glass design with exact provided colors
        wp_add_inline_style('bootstrap-css', '
            #uptc-unused-plugins-themes-page {
                background: #540B0E; /* Base blood-of-Sun-1 */
                padding: 20px;
                border-radius: 20px;
                backdrop-filter: blur(10px);
                box-shadow: 10px 10px 30px rgba(51, 92, 103, 0.3), -10px -10px 30px rgba(255, 243, 176, 0.5);
                font-family: "Raleway", sans-serif;
                position: relative;
                min-height: 380px;
            }
            #uptc-unused-plugins-themes-page h1, 
            #uptc-unused-plugins-themes-page h2, 
            #uptc-unused-plugins-themes-page h3 {
                color: #E09F3E; /* Base blood-of-Sun-5 */
                font-family: "Playfair Display", serif;
            }
            .metrics-bar {
                display: flex;
                justify-content: space-around;
                align-items: center;
                background: #335C67; /* Base blood-of-Sun-3 */
                border-radius: 15px;
                padding: 20px;
                backdrop-filter: blur(15px);
                box-shadow: 10px 10px 30px rgba(51, 92, 103, 0.2), -10px -10px 30px rgba(255, 243, 176, 0.3);
                color: #FFF3B0; /* Base blood-of-Sun-4 */
                margin-bottom: 30px;
            }
            .metrics-bar div {
                flex: 1;
                text-align: center;
                font-weight: 600;
            }
            .metrics-bar div span {
                display: block;
                font-size: 1.5rem;
                font-family: "EB Garamond", serif;
                color: #FFF3B0; /* Base blood-of-Sun-4 */
            }
            table.table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0 15px;
            }
            .table-striped tbody tr {
                background: #335C67; /* Base blood-of-Sun-3 */
                border-radius: 15px;
                color: #FFF3B0; /* Base blood-of-Sun-4 */
                transition: all 0.3s ease;
                backdrop-filter: blur(5px);
            }
            .table-striped tbody tr:hover {
                background: rgba(224, 159, 62, 0.3); /* Base blood-of-Sun-5 with transparency */
                transform: scale(1.02);
            }
            .table-striped th {
                background: #A62E38; /* Base blood-of-Sun-2 */
                color: #FFF3B0; /* Base blood-of-Sun-4 */
                border: none;
                font-family: "Playfair Display", serif;
                text-align: center;
            }
            .btn {
                background-color: #A62E38; /* Base blood-of-Sun-2 */
                border: none;
                box-shadow: 5px 5px 15px rgba(51, 92, 103, 0.5), -5px -5px 15px rgba(255, 243, 176, 0.5);
                transition: all 0.3s ease;
                font-family: "EB Garamond", serif;
                color: #FFF3B0; /* Base blood-of-Sun-4 */
            }
            .btn:hover {
                background-color: #540B0E; /* Base blood-of-Sun-1 */
                color: #FFF3B0; /* Base blood-of-Sun-4 */
                transform: scale(1.05);
            }
            .select-all {
                cursor: pointer;
            }
            footer {
                margin-top: 20px;
                text-align: center;
                color: #FFF3B0; /* Base blood-of-Sun-4 */
                font-family: "Raleway", sans-serif;
            }
            #loader {
                width: 80px;
                height: 40px;
                position: fixed;
                top: 50%;
                left: 50%;
                margin: -20px -40px;
                z-index: 1000;
                display: none;
            }
        ');

        // Inline script for DataTables initialization and loader display
        wp_add_inline_script('datatables-js', '
            jQuery(document).ready(function($) {
                $(".data-table").DataTable();
                $(".select-all").on("click", function() {
                    var table = $(this).closest("table");
                    $("input[type=\'checkbox\']", table).prop("checked", this.checked);
                });

                $("form").on("submit", function() {
                    $("#loader").fadeIn();
                });

                $(window).on("beforeunload", function() {
                    $("#loader").fadeIn();
                });
            });
        ');
    }
}

// Hook to add a menu item in the network admin menu
add_action('network_admin_menu', 'uptc_add_menu_page');
add_action('admin_menu', 'uptc_add_menu_page');

function uptc_add_menu_page() {
    add_menu_page(
        'Unused Plugins and Themes',
        'Unused Plugins/Themes',
        'manage_options',
        'uptc-unused-plugins-themes',
        'uptc_display_unused_plugins_themes',
        'dashicons-admin-plugins',
        20
    );
}

// Function to delete selected plugins and themes
function uptc_delete_plugins_themes() {
    if (isset($_POST['delete_plugins']) && isset($_POST['unused_plugins']) && check_admin_referer('uptc_delete_plugins_action', 'uptc_nonce_field')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $plugins_to_delete = $_POST['unused_plugins'];
        foreach ($plugins_to_delete as $plugin) {
            if (file_exists(WP_PLUGIN_DIR . '/' . $plugin)) {
                if (is_plugin_active($plugin)) {
                    deactivate_plugins($plugin); // Deactivate the plugin if it is active
                }
                $result = delete_plugins([$plugin]); // Delete the plugin

                if (is_wp_error($result)) {
                    error_log('Failed to delete plugin: ' . $plugin . ' Error: ' . $result->get_error_message());
                } else {
                    error_log('Successfully deleted plugin: ' . $plugin);
                }
            }
        }
    }

    if (isset($_POST['delete_themes']) && isset($_POST['unused_themes']) && check_admin_referer('uptc_delete_plugins_action', 'uptc_nonce_field')) {
        require_once ABSPATH . 'wp-admin/includes/theme.php';

        $themes_to_delete = $_POST['unused_themes'];
        foreach ($themes_to_delete as $theme) {
            if (wp_get_theme($theme)->exists()) {
                $result = delete_theme($theme); // Delete the theme

                if (is_wp_error($result)) {
                    error_log('Failed to delete theme: ' . $theme . ' Error: ' . $result->get_error_message());
                } else {
                    error_log('Successfully deleted theme: ' . $theme);
                }
            }
        }
    }
}

add_action('admin_init', 'uptc_delete_plugins_themes');

// Function to display unused plugins and themes
function uptc_display_unused_plugins_themes() {
    global $wpdb;

    $all_plugins = get_plugins();
    $active_plugins = array();
    $all_themes = wp_get_themes();
    $active_themes = array();

    $network_active_plugins = is_multisite() ? array_keys(get_site_option('active_sitewide_plugins', [])) : [];

    if (is_multisite()) {
        $sites = get_sites();

        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);

            $site_active_plugins = get_option('active_plugins', array());
            $active_plugins[$site->blog_id] = $site_active_plugins;

            $site_theme = wp_get_theme();
            $active_themes[$site->blog_id] = array(
                'name' => $site_theme->get('Name'),
                'stylesheet' => $site_theme->get_stylesheet(),
                'template' => $site_theme->get_template()
            );

            restore_current_blog();
        }
    } else {
        $site_active_plugins = get_option('active_plugins', array());
        $active_plugins[1] = $site_active_plugins;

        $site_theme = wp_get_theme();
        $active_themes[1] = array(
            'name' => $site_theme->get('Name'),
            'stylesheet' => $site_theme->get_stylesheet(),
            'template' => $site_theme->get_template()
        );
    }

    $all_plugin_keys = array_keys($all_plugins);
    $used_plugins = array_unique(array_merge($network_active_plugins, call_user_func_array('array_merge', $active_plugins)));
    $unused_plugins = array_diff($all_plugin_keys, $used_plugins);

    $all_theme_keys = array_keys($all_themes);
    $used_themes = array();
    foreach ($active_themes as $theme) {
        $used_themes[] = $theme['stylesheet'];
    }
    $unused_themes = array_diff($all_theme_keys, $used_themes);

    echo '<div class="wrap" id="uptc-unused-plugins-themes-page">';
    echo '<div id="loader">
            <div class="ls-particles ls-part-1"></div>
            <div class="ls-particles ls-part-2"></div>
            <div class="ls-particles ls-part-3"></div>
            <div class="ls-particles ls-part-4"></div>
            <div class="ls-particles ls-part-5"></div>
            <div class="lightsaber ls-left ls-green"></div>
            <div class="lightsaber ls-right ls-red"></div>
          </div>';
    echo '<h1>Unused Plugins and Themes</h1>';

    // Metrics Bar
    echo '<div class="metrics-bar">';
    echo '<div><strong>Installation Type:</strong> <span>' . (is_multisite() ? 'Multisite' : 'Single Site') . '</span></div>';
    echo '<div><strong>Disk Usage:</strong> <span>' . size_format(disk_total_space(ABSPATH) - disk_free_space(ABSPATH)) . '</span></div>';
    $table_count = count($wpdb->get_results('SHOW TABLES'));
    echo '<div><strong>Number of Tables:</strong> <span>' . $table_count . '</span></div>';
    $table_size = $wpdb->get_var("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) FROM information_schema.TABLES WHERE table_schema = '" . DB_NAME . "'");
    echo '<div><strong>Total Table Size:</strong> <span>' . $table_size . ' MB</span></div>';
    echo '</div>';

    // Active plugins table
    echo '<h2>Active Plugins</h2>';
    echo '<table class="table table-striped data-table">';
    echo '<thead><tr><th>Plugin</th><th>Sites</th><th>Database Table</th><th>Type</th></tr></thead>';
    echo '<tbody>';
    foreach ($all_plugins as $plugin_file => $plugin_data) {
        if (in_array($plugin_file, $used_plugins)) {
            $sites_using_plugin = [];
            foreach ($active_plugins as $blog_id => $plugins) {
                if (in_array($plugin_file, $plugins)) {
                    $site_name = is_multisite() ? get_blog_details($blog_id)->blogname : get_option('blogname');
                    $sites_using_plugin[] = $site_name;
                }
            }
            $type = in_array($plugin_file, $network_active_plugins) ? 'Network Active' : 'Site Active';
            $db_table = 'wp_' . sanitize_key($plugin_file) . '_data';

            echo '<tr>';
            echo '<td>' . esc_html($plugin_data['Name']) . ' (' . esc_html($plugin_file) . ')</td>';
            echo '<td>' . implode(', ', $sites_using_plugin) . '</td>';
            echo '<td>' . esc_html($db_table) . '</td>';
            echo '<td>' . esc_html($type) . '</td>';
            echo '</tr>';
        }
    }
    echo '</tbody></table>';

    // Unused plugins table with deletion option
    echo '<h2>Unused Plugins</h2>';
    if (!empty($unused_plugins)) {
        echo '<form method="post">';
        wp_nonce_field('uptc_delete_plugins_action', 'uptc_nonce_field');
        echo '<table class="table table-striped data-table">';
        echo '<thead><tr><th><input type="checkbox" class="select-all"></th><th>Plugin</th><th>Database Table</th></tr></thead>';
        echo '<tbody>';
        foreach ($unused_plugins as $plugin) {
            $db_table = 'wp_' . sanitize_key($plugin) . '_data';
            echo '<tr>';
            echo '<td><input type="checkbox" name="unused_plugins[]" value="' . esc_attr($plugin) . '"></td>';
            echo '<td>' . esc_html($all_plugins[$plugin]['Name']) . ' (' . esc_html($plugin) . ')</td>';
            echo '<td>' . esc_html($db_table) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '<p><input type="submit" name="delete_plugins" class="btn btn-danger mt-3" value="Delete Selected Plugins"></p>';
        echo '</form>';
    } else {
        echo '<p>All installed plugins are currently in use.</p>';
    }

    // Unused themes table with checkboxes
    echo '<h2>Unused Themes</h2>';
    if (!empty($unused_themes)) {
        echo '<form method="post">';
        wp_nonce_field('uptc_delete_plugins_action', 'uptc_nonce_field');
        echo '<table class="table table-striped data-table">';
        echo '<thead><tr><th><input type="checkbox" class="select-all"></th><th>Theme</th><th>Theme Name</th></tr></thead>';
        echo '<tbody>';
        foreach ($unused_themes as $theme) {
            echo '<tr>';
            echo '<td><input type="checkbox" name="unused_themes[]" value="' . esc_attr($theme) . '"></td>';
            echo '<td>' . esc_html($theme) . '</td>';
            echo '<td>' . esc_html($all_themes[$theme]->get('Name')) . '</td>';
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '<p><input type="submit" name="delete_themes" class="btn btn-danger mt-3" value="Delete Selected Themes"></p>';
        echo '</form>';
    } else {
        echo '<p>All installed themes are currently in use.</p>';
    }

    echo '</div>';
}
?>
