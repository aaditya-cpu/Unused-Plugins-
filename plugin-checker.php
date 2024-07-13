<?php
/*
Plugin Name: Unused Plugins and Themes Checker
Description: A plugin to identify unused plugins and themes in a WordPress multisite network.
Version: 1.0
Author: Aaditya Uzumaki 
Website:https://goenka.xyz
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
        $active_plugins = array_merge($active_plugins, $site_active_plugins);

        $site_theme = wp_get_theme();
        $active_themes[] = $site_theme->get_stylesheet();

        restore_current_blog();
    }

    $unused_plugins = array_diff(array_keys($all_plugins), $active_plugins);
    $unused_themes = array_diff(array_keys($all_themes), $active_themes);

    echo '<div class="wrap">';
    echo '<h1>Unused Plugins and Themes</h1>';

    echo '<h2>Unused Plugins</h2>';
    if (!empty($unused_plugins)) {
        echo '<ul>';
        foreach ($unused_plugins as $plugin) {
            echo '<li>' . esc_html($all_plugins[$plugin]['Name']) . ' (' . esc_html($plugin) . ')</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>All plugins are in use.</p>';
    }

    echo '<h2>Unused Themes</h2>';
    if (!empty($unused_themes)) {
        echo '<ul>';
        foreach ($unused_themes as $theme) {
            echo '<li>' . esc_html($all_themes[$theme]->get('Name')) . ' (' . esc_html($theme) . ')</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>All themes are in use.</p>';
    }

    echo '</div>';
}
?>
