<?php
/*
Plugin Name: Unused Plugins and Themes Checker
Description: A plugin to identify and delete unused plugins and themes in a WordPress multisite or single-site installation, with advanced metrics and classification.
Version: 2.1
Author: Aaditya Uzumaki
Website: https://goenka.xyz
*/

class UnusedPluginsThemesChecker {

    protected $is_multisite;

    public function __construct() {
        $this->is_multisite = is_multisite();

        // Initialize actions
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action($this->is_multisite ? 'network_admin_menu' : 'admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'process_deletions']);
    }

    /**
     * Enqueue styles and scripts
     */
    public function enqueue_assets() {
        wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
        wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', [], null, true);
        wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css');
        wp_enqueue_script('jquery');
        wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js', ['jquery'], null, true);

wp_add_inline_style('bootstrap-css', '
    body {
        background-color: #540B0E; /* Blood-of-Sun theme */
        color: #FFF3B0;
        font-family: "Raleway", sans-serif;
    }
    h1, h2, h3, h4, h5, h6 {
        color: #FFF3B0;
        font-family: "Playfair Display", serif;
    }
    .data-table, .data-table th, .data-table td {
        background-color: #A62E38;
        color: #FFF3B0;
        border-color: #335C67;
    }
    .dataTables_wrapper .dataTables_filter input, 
    .dataTables_wrapper .dataTables_length select {
        background-color: #FFF3B0;
        border: 1px solid #A62E38;
        color: #540B0E;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        background-color: #A62E38;
        border: 1px solid #540B0E;
        color: #FFF3B0;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background-color: #E09F3E;
        color: #540B0E;
    }
    /* Buttons */
    button, .btn {
        background-color: #335C67 !important; /* Theme-specific color */
        border-color: #335C67 !important;
        color: #FFF3B0 !important;
        font-family: "EB Garamond", serif;
    }
    button:hover, .btn:hover {
        background-color: #A62E38 !important; /* On hover */
        border-color: #A62E38 !important;
        color: #FFF3B0 !important;
    }
    .btn-danger {
        background-color: #A62E38 !important; /* Danger button */
        border-color: #A62E38 !important;
    }
    .btn-danger:hover {
        background-color: #540B0E !important; /* Hover effect for danger */
        border-color: #540B0E !important;
    }
    /* Metrics Bar */
    .metrics-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #335C67; /* Base Blood-of-Sun Theme */
        padding: 10px 20px;
        border-radius: 8px;
        color: #FFF3B0;
        font-family: "Raleway", sans-serif;
        margin-bottom: 20px;
    }
    .metrics-bar div {
        flex: 1;
        text-align: center;
        font-size: 1rem;
        font-weight: 600;
    }
    .metrics-bar div:not(:last-child) {
        border-right: 1px solid #A62E38; /* Separator */
    }
    .metrics-bar div span {
        display: block;
        font-size: 1.2rem;
        font-weight: bold;
        color: #FFF3B0;
        font-family: "Playfair Display", serif;
    }
    /* Loader */
    .loader {
        animation: rotate 1s infinite;
        height: 50px;
        width: 50px;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 1000;
        display: none;
    }
    .loader:before, .loader:after {
        border-radius: 50%;
        content: "";
        display: block;
        height: 20px;
        width: 20px;
    }
    .loader:before {
        animation: ball1 1s infinite;
        background-color: #540B0E;
        box-shadow: 30px 0 0 #A62E38;
        margin-bottom: 10px;
    }
    .loader:after {
        animation: ball2 1s infinite;
        background-color: #335C67;
        box-shadow: 30px 0 0 #E09F3E;
    }
    @keyframes rotate {
        0%, 100% { transform: rotate(0deg) scale(0.8); }
        50% { transform: rotate(360deg) scale(1.2); }
    }
    @keyframes ball1 {
        0% { box-shadow: 30px 0 0 #A62E38; }
        50% { box-shadow: 0 0 0 #A62E38; transform: translate(15px, 15px); }
        100% { box-shadow: 30px 0 0 #A62E38; }
    }
    @keyframes ball2 {
        0% { box-shadow: 30px 0 0 #E09F3E; }
        50% { box-shadow: 0 0 0 #E09F3E; transform: translate(15px, 15px); }
        100% { box-shadow: 30px 0 0 #E09F3E; }
    }
    footer {
        margin-top: 20px;
        text-align: center;
        color: #FFF3B0;
        font-family: "Raleway", sans-serif;
    }
');


        // Inline scripts
        wp_add_inline_script('datatables-js', '
            jQuery(document).ready(function($) {
                $(".data-table").DataTable();
                $(".select-all").on("click", function() {
                    var table = $(this).closest("table");
                    $("input[type=\'checkbox\']", table).prop("checked", this.checked);
                });
            });
        ');
    }

    /**
     * Add menu page
     */
    public function add_menu_page() {
        $menu_title = $this->is_multisite ? 'Unused Plugins/Themes' : 'Unused Items';
        add_menu_page(
            'Unused Plugins and Themes',
            $menu_title,
            'manage_options',
            'uptc-unused-plugins-themes',
            [$this, 'render_admin_page'],
            'dashicons-admin-plugins',
            20
        );
    }

    /**
     * Process plugin and theme deletions
     */
    public function process_deletions() {
        if (isset($_POST['delete_plugins']) && isset($_POST['unused_plugins'])) {
            foreach ($_POST['unused_plugins'] as $plugin) {
                delete_plugins([$plugin]);
            }
        }

        if (isset($_POST['delete_themes']) && isset($_POST['unused_themes'])) {
            foreach ($_POST['unused_themes'] as $theme) {
                delete_theme($theme);
            }
        }
    }

    /**
     * Get metrics for the top bar
     */
    protected function get_metrics() {
        global $wpdb;

        $installation_type = $this->is_multisite ? 'Multisite' : 'Single Site';
        $disk_usage = size_format(disk_total_space(ABSPATH) - disk_free_space(ABSPATH));
        $table_count = count($wpdb->get_results('SHOW TABLES'));
        $table_size = $wpdb->get_var("
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) 
            FROM information_schema.TABLES 
            WHERE table_schema = '" . DB_NAME . "'
        ");

        return [
            'installation_type' => $installation_type,
            'disk_usage' => $disk_usage,
            'table_count' => $table_count,
            'table_size' => "{$table_size} MB"
        ];
    }

    /**
     * Get active plugins and themes
     */
    protected function get_active_items() {
        $plugins_by_site = [];
        $themes_by_site = [];

        if ($this->is_multisite) {
            $sites = get_sites();
            foreach ($sites as $site) {
                switch_to_blog($site->blog_id);
                $plugins_by_site[$site->blog_id] = get_option('active_plugins', []);
                $themes_by_site[$site->blog_id] = wp_get_theme()->get_stylesheet();
                restore_current_blog();
            }
        } else {
            $plugins_by_site[1] = get_option('active_plugins', []);
            $themes_by_site[1] = wp_get_theme()->get_stylesheet();
        }

        return [$plugins_by_site, $themes_by_site];
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        $all_plugins = get_plugins();
        $all_themes = wp_get_themes();
        $network_active_plugins = $this->is_multisite ? array_keys(get_site_option('active_sitewide_plugins', [])) : [];

        list($plugins_by_site, $themes_by_site) = $this->get_active_items();

        $active_plugins = array_unique(array_merge($network_active_plugins, ...array_values($plugins_by_site)));
        $used_themes = array_unique(array_values($themes_by_site));

        $unused_plugins = array_diff(array_keys($all_plugins), $active_plugins);
        $unused_themes = array_diff(array_keys($all_themes), $used_themes);

        $metrics = $this->get_metrics();

        echo '<div class="metrics-bar">';
        foreach ($metrics as $label => $value) {
            echo "<div><strong>{$label}:</strong><br>{$value}</div>";
        }
        echo '</div>';

        $this->render_table('Active Plugins', $all_plugins, $plugins_by_site, $network_active_plugins, $active_plugins, true);
        $this->render_unused_section('Unused Plugins', $unused_plugins, $all_plugins);
        $this->render_unused_section('Unused Themes', $unused_themes, $all_themes);

        echo '<footer class="mt-4">Created by <a href="https://goenka.xyz" target="_blank">Aaditya Uzumaki</a></footer>';
    }

    /**
     * Render active plugins table
     */
    protected function render_table($title, $items, $items_by_site, $network_active, $active_items, $is_plugin = true) {
        echo "<h2>{$title}</h2>";
        echo '<table class="table table-bordered table-hover data-table">';
        echo '<thead><tr><th>Name</th><th>Sites</th><th>Database Tables</th><th>Type</th></tr></thead>';
        echo '<tbody>';

        foreach ($items as $file => $data) {
            if (in_array($file, $active_items)) {
                $sites = array_keys(array_filter($items_by_site, fn($site_items) => in_array($file, $site_items)));
                $db_table = 'wp_' . sanitize_key($file) . '_data';
                $type = in_array($file, $network_active) ? 'Network Active' : 'Site Active';
                echo '<tr>';
                echo "<td>{$data['Name']}</td>";
                echo '<td>' . implode(', ', $sites) . '</td>';
                echo "<td>{$db_table}</td>";
                echo "<td>{$type}</td>";
                echo '</tr>';
            }
        }

        echo '</tbody></table>';
    }

    /**
     * Render unused plugins/themes section
     */
    protected function render_unused_section($title, $unused_items, $all_items) {
        echo "<h2>{$title}</h2>";

        if (!empty($unused_items)) {
            echo '<form method="post">';
            echo '<table class="table table-bordered table-hover data-table">';
            echo '<thead><tr><th><input type="checkbox" class="select-all"></th><th>Name</th><th>Database Tables</th></tr></thead>';
            echo '<tbody>';

            foreach ($unused_items as $item) {
                $db_table = 'wp_' . sanitize_key($item) . '_data';
                echo '<tr>';
                echo '<td><input type="checkbox" name="unused_' . strtolower($title) . '[]" value="' . esc_attr($item) . '"></td>';
                echo '<td>' . esc_html($all_items[$item]['Name']) . '</td>';
                echo "<td>{$db_table}</td>";
                echo '</tr>';
            }

            echo '</tbody></table>';
            echo '<button type="submit" name="delete_' . strtolower(str_replace(' ', '_', $title)) . '" class="btn btn-danger mt-3">Delete Selected ' . $title . '</button>';
            echo '</form>';
        } else {
            echo "<p>All {$title} are in use.</p>";
        }
    }
}

// Initialize the plugin
new UnusedPluginsThemesChecker();
?>
