<?php
/*
Plugin Name: Unused Plugins and Themes Checker
Description: A plugin to identify and delete unused plugins and themes in a WordPress multisite network.
Version: 1.2
Author: Aaditya Uzumaki 
Website: https://goenka.xyz
*/

// Enqueue Bootstrap CSS
add_action('admin_enqueue_scripts', 'uptc_enqueue_styles');
function uptc_enqueue_styles() {
    wp_enqueue_style('bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
}

// Hook to add a menu item in the network admin menu
add_action('network_admin_menu', 'uptc_add_menu_page');

function uptc_add_menu_page() {
    add_menu_page(
        'Unused Plugins and Themes',
        'Unused Plugins/Themes',
        'manage_network',
        'uptc-unused-plugins-themes',
        'uptc_display_unused_plugins_themes',
        'dashicons-admin-plugins',
        20
    );
}

// Function to delete selected plugins
function uptc_delete_plugins() {
    if (isset($_POST['delete_plugins']) && isset($_POST['unused_plugins'])) {
        $plugins_to_delete = $_POST['unused_plugins'];
        foreach ($plugins_to_delete as $plugin) {
            delete_plugins([$plugin]);
        }
    }
}

add_action('admin_init', 'uptc_delete_plugins');

function uptc_display_unused_plugins_themes() {
    global $wpdb;

    $all_plugins = get_plugins();
    $active_plugins = array();
    $all_themes = wp_get_themes();
    $active_themes = array();

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

    $all_plugin_keys = array_keys($all_plugins);
    $all_theme_keys = array_keys($all_themes);

    $used_plugins = call_user_func_array('array_merge', $active_plugins);
    $unused_plugins = array_diff($all_plugin_keys, $used_plugins);

    $used_themes = array();
    foreach ($active_themes as $theme) {
        $used_themes[] = $theme['stylesheet'];
    }
    $unused_themes = array_diff($all_theme_keys, $used_themes);

    echo '<div class="wrap">';
    echo '<h1>Unused Plugins and Themes</h1>';

    // Active plugins table
    echo '<h2>Active Plugins</h2>';
    echo '<table class="table table-striped">';
    echo '<thead><tr><th>Plugin</th><th>Sites</th><th>Count</th></tr></thead>';
    echo '<tbody>';
    foreach ($all_plugins as $plugin_file => $plugin_data) {
        if (in_array($plugin_file, $used_plugins)) {
            $count = 0;
            $sites_using_plugin = array();
            foreach ($active_plugins as $blog_id => $plugins) {
                if (in_array($plugin_file, $plugins)) {
                    $sites_using_plugin[] = get_blog_details($blog_id)->blogname;
                    $count++;
                }
            }
            echo '<tr>';
            echo '<td>' . esc_html($plugin_data['Name']) . ' (' . esc_html($plugin_file) . ')</td>';
            echo '<td>' . implode(', ', $sites_using_plugin) . '</td>';
            echo '<td>' . esc_html($count) . '</td>';
            echo '</tr>';
        }
    }
    echo '</tbody></table>';

    // Unused plugins table with deletion option
    echo '<h2>Unused Plugins</h2>';
    if (!empty($unused_plugins)) {
        echo '<form method="post">';
        echo '<table class="table table-striped">';
        echo '<thead><tr><th>Plugin</th><th>Select</th></tr></thead>';
        echo '<tbody>';
        $counter = 0;
        foreach ($unused_plugins as $plugin) {
            echo '<tr>';
            echo '<td>' . esc_html($all_plugins[$plugin]['Name']) . ' (' . esc_html($plugin) . ')</td>';
            echo '<td><input type="checkbox" name="unused_plugins[]" value="' . esc_attr($plugin) . '"></td>';
            echo '</tr>';
            $counter++;
            if ($counter % 20 == 0) {
                echo '<tr><td colspan="2">';
                echo '<button type="submit" name="delete_plugins" class="btn btn-danger">Delete Selected Plugins</button>';
                echo '</td></tr>';
            }
        }
        echo '</tbody></table>';
        echo '<button type="submit" name="delete_plugins" class="btn btn-danger">Delete Selected Plugins</button>';
        echo '</form>';
    } else {
        echo '<p>All plugins are in use.</p>';
    }

    // Active themes table
    echo '<h2>Active Themes</h2>';
    echo '<table class="table table-striped">';
    echo '<thead><tr><th>Theme</th><th>Sites</th><th>Count</th><th>Parent Theme</th></tr></thead>';
    echo '<tbody>';
    foreach ($all_themes as $theme_slug => $theme) {
        if (in_array($theme_slug, $used_themes)) {
            $count = 0;
            $sites_using_theme = array();
            foreach ($active_themes as $blog_id => $theme_info) {
                if ($theme_info['stylesheet'] == $theme_slug) {
                    $sites_using_theme[] = get_blog_details($blog_id)->blogname;
                    $count++;
                }
            }
            echo '<tr>';
            echo '<td>' . esc_html($theme->get('Name')) . ' (' . esc_html($theme_slug) . ')</td>';
            echo '<td>' . implode(', ', $sites_using_theme) . '</td>';
            echo '<td>' . esc_html($count) . '</td>';
            echo '<td>' . esc_html($theme->parent() ? $theme->parent()->get('Name') : 'None') . '</td>';
            echo '</tr>';
        }
    }
    echo '</tbody></table>';

    // Unused themes table
    echo '<h2>Unused Themes</h2>';
    if (!empty($unused_themes)) {
        echo '<table class="table table-striped">';
        echo '<thead><tr><th>Theme</th></tr></thead>';
        echo '<tbody>';
        foreach ($unused_themes as $theme) {
            echo '<tr><td>' . esc_html($all_themes[$theme]->get('Name')) . ' (' . esc_html($theme) . ')</td></tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>All themes are in use.</p>';
    }

    echo '</div>';
}
?>
