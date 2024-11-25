Unused Plugins and Themes Checker

Version: 2.2Author: Aaditya UzumakiWebsite: goenka.xyz

Description

The Unused Plugins and Themes Checker plugin helps you manage your WordPress installation by identifying and deleting unused plugins and themes. It works seamlessly with both single-site and multisite installations, offering advanced metrics and classifications for better decision-making.

Features

Advanced Metrics Bar: Displays installation type, disk usage, database table count, and database size.

Plugin Classification: Differentiates between Network Active and Site Active plugins in multisite installations.

Unused Plugin and Theme Detection: Scans for unused plugins and themes, presenting them for easy management.

User-Friendly Interface: Clean, responsive design styled with the "Blood-of-Sun" theme.

Deletion Features: Allows safe and easy deletion of unused plugins and themes.

License

This plugin is licensed under the Attribution Assurance License (AAL). You may modify, distribute, or use the plugin in your projects under the following conditions:

Attribution: You must give appropriate credit to the original author, Aaditya Uzumaki, by including the original plugin name and author details in any derivative works or distributions.

License Inclusion: The same license must apply to derivative works, ensuring that others can benefit under the same terms.

SPDX-License-Identifier: AAL-1.0

For details, visit the AAL License.

Explanation of Classes and Program Flow

Classes and Their Responsibilities

UnusedPluginsThemesChecker

The main class of the plugin encapsulates all features and handles the following responsibilities:

__construct():

Detects if the installation is multisite or single-site.

Registers WordPress hooks for enqueueing assets, creating menu pages, and handling deletions.

enqueue_assets():

Loads styles and scripts for the plugin.

Adds inline styles and JavaScript for a polished UI.

add_menu_page():

Adds a menu page to the admin or network admin dashboard, depending on the installation type.

process_deletions():

Deletes selected unused plugins and themes based on user input.

get_metrics():

Fetches key installation metrics, including:

Installation type (single-site or multisite).

Disk usage.

Number of database tables.

Total database size.

get_active_items():

Retrieves active plugins and themes, grouped by site for multisite installations.

render_admin_page():

Generates the admin page, including:

A metrics bar with key statistics.

A table of active plugins/themes.

A section for unused plugins/themes with deletion options.

render_table():

Displays active plugins/themes with associated sites, database tables, and classifications (e.g., "Network Active" or "Site Active").

render_unused_section():

Displays unused plugins/themes for review and deletion.

Program Flow

Initialization:

The UnusedPluginsThemesChecker class is instantiated during plugin activation.

It determines the installation type and registers hooks.

Asset Loading:

Enqueues styles and scripts for the admin interface.

Admin Menu:

Adds a menu item to the admin or network admin dashboard for accessing the plugin interface.

Interface Rendering:

Displays:

Metrics bar for installation statistics.

Tables of active and unused plugins/themes.

Buttons for managing and deleting unused items.

Deletion Workflow:

Allows users to select and delete unused plugins/themes safely.

Installation

From WordPress Admin:

Navigate to Plugins > Add New.

Click Upload Plugin.

Upload the plugin ZIP file and click Install Now.

Once installed, click Activate.

Manual Installation:

Upload the plugin folder to /wp-content/plugins/.

Activate the plugin via the Plugins menu in WordPress.

Usage

For Single-Site Installations:

Navigate to the Admin Dashboard.

Click Unused Items in the menu.

Review active and unused plugins/themes.

Delete unused plugins/themes as needed.

For Multisite Installations:

Navigate to the Network Admin Dashboard.

Click Unused Plugins/Themes in the menu.

Review active and unused plugins/themes network-wide.

Delete unused items as needed.

Changelog

2.2 (Latest Release)

Fixed theme deletion issues.

Added a loading animation for plugin and theme deletions.

Enhanced UI consistency for better user experience.

2.1

Enhanced UI with the Blood-of-Sun theme.

Introduced a metrics bar for displaying installation stats.

Improved classification for multisite plugins.

2.0

Added multisite support.

Introduced deletion features for unused plugins and themes.

Categorized active plugins/themes.
