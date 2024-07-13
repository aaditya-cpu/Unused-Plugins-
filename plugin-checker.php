<?php
/*
Plugin Name: Unused Plugins and Themes Checker
Description: A plugin to identify unused plugins and themes in a WordPress multisite network.
Version: 1.1
Author: Aaditya Uzumaki 
Website: https://goenka.xyz
*/

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
    echo '<table class="widefat fixed" cellspacing="0">';
    echo '<thead><tr><th>Plugin</th><th>Sites</th></tr></thead>';
    echo '<tbody>';
    foreach ($all_plugins as $plugin_file => $plugin_data) {
        if (in_array($plugin_file, $used_plugins)) {
            echo '<tr>';
            echo '<td>' . esc_html($plugin_data['Name']) . ' (' . esc_html($plugin_file) . ')</td>';
            echo '<td>';
            $sites_using_plugin = array();
            foreach ($active_plugins as $blog_id => $plugins) {
                if (in_array($plugin_file, $plugins)) {
                    $sites_using_plugin[] = get_blog_details($blog_id)->blogname;
                }
            }
            echo implode(', ', $sites_using_plugin);
            echo '</td>';
            echo '</tr>';
        }
    }
    echo '</tbody></table>';

    // Unused plugins table
    echo '<h2>Unused Plugins</h2>';
    if (!empty($unused_plugins)) {
        echo '<table class="widefat fixed" cellspacing="0">';
        echo '<thead><tr><th>Plugin</th></tr></thead>';
        echo '<tbody>';
        foreach ($unused_plugins as $plugin) {
            echo '<tr><td>' . esc_html($all_plugins[$plugin]['Name']) . ' (' . esc_html($plugin) . ')</td></tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>All plugins are in use.</p>';
    }

    // Active themes table
    echo '<h2>Active Themes</h2>';
    echo '<table class="widefat fixed" cellspacing="0">';
    echo '<thead><tr><th>Theme</th><th>Sites</th></tr></thead>';
    echo '<tbody>';
    foreach ($all_themes as $theme_slug => $theme) {
        if (in_array($theme_slug, $used_themes)) {
            echo '<tr>';
            echo '<td>' . esc_html($theme->get('Name')) . ' (' . esc_html($theme_slug) . ')</td>';
            echo '<td>';
            $sites_using_theme = array();
            foreach ($active_themes as $blog_id => $theme_info) {
                if ($theme_info['stylesheet'] == $theme_slug) {
                    $sites_using_theme[] = get_blog_details($blog_id)->blogname;
                }
            }
            echo implode(', ', $sites_using_theme);
            echo '</td>';
            echo '</tr>';
        }
    }
    echo '</tbody></table>';

    // Unused themes table
    echo '<h2>Unused Themes</h2>';
    if (!empty($unused_themes)) {
        echo '<table class="widefat fixed" cellspacing="0">';
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
