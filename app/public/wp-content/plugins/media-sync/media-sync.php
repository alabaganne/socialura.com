<?php

/**
 * Plugin Name: Media Sync
 * Plugin URI: https://wordpress.org/plugins/media-sync/
 * Description: Simple plugin to scan uploads directory and bring files to Media Library.
 * Version: 1.4.8
 * Author: Media Sync Team
 * Author URI: https://mediasyncplugin.com/?utm_source=wordpress_dashboard&utm_medium=plugins_page&utm_campaign=pdal
 * License: GPLv2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: media-sync
 * Domain Path: /languages
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! is_admin() ) {
    return;
}

include( plugin_dir_path(__FILE__) . 'includes/MediaSync.class.php');


add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'media_sync_link_to_main_plugin_page' );

/**
 * Add link below plugin name on 'Plugins' page
 *
 * @since 0.1.0
 */
function media_sync_link_to_main_plugin_page( $links ) {
    $title = __('Media Sync', 'media-sync');
    $links[] = '<a href="'. esc_url( get_admin_url(null, 'upload.php?page=media-sync-page') ) .'">' . $title . '</a>';
    $links[] = '<a href="https://mediasyncplugin.com/?utm_source=wordpress_dashboard&utm_medium=plugins_page&utm_campaign=pdl" target="_blank">' . __('Media Sync Pro', 'media-sync') . '</a>';
    return $links;
}



add_action( 'admin_menu', 'media_sync_add_menu_items' );

/**
 * Add menu item for this plugin
 *
 * @since 0.1.0
 */
function media_sync_add_menu_items() {

    $title = __('Media Sync', 'media-sync');

    // Add sub item to Media menu
    if(MediaSync::media_sync_user_has_general_access()) {
        add_media_page( $title, $title, 'read', 'media-sync-page', 'media_sync_main_page' );
    }

    // Show options menu only if user can manage options
    if ( current_user_can( 'manage_options' ) ) {

        // Add sub item to Settings menu
        add_options_page( $title, $title, 'manage_options', 'media-sync-options', 'media_sync_options_page' );

        // Prepare plugin settings
        add_action( 'admin_init', 'media_sync_options_setup' );
    }
}


add_action( 'admin_enqueue_scripts', 'media_sync_load_admin_scripts' );

/**
 * Load Admin CSS and JS files
 *
 * @since 0.1.0
 * @param string $hook
 * @return void
 */
function media_sync_load_admin_scripts( $hook ) {

    if ( !($hook == 'media_page_media-sync-page' || $hook == 'settings_page_media-sync-options') ) {
        return;
    }

    $plugin_data = get_file_data(__FILE__, array('v' => 'Version'), false);
    $version = !empty($plugin_data['v']) ? $plugin_data['v'] : '1.2.3';

    $js_dir  = plugin_dir_url( __FILE__ ) . 'admin/js/';
    $css_dir = plugin_dir_url( __FILE__ ) . 'admin/css/';

    wp_register_script( 'media-sync-js-admin-script', $js_dir . 'script.js', array('jquery'), $version, true );
    wp_enqueue_script( 'media-sync-js-admin-script' );

    wp_enqueue_script( 'media-sync-js-admin-ajax-script', $js_dir . 'ajax_script.js', array('jquery'), $version );
    wp_localize_script( 'media-sync-js-admin-ajax-script', 'ajax_data', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'security' => wp_create_nonce( "media_sync_import_files" )
    ));

    wp_register_style( 'media-sync-css-admin-style', $css_dir . 'style.css', '', $version );
    wp_enqueue_style( 'media-sync-css-admin-style' );
}



add_action( 'wp_ajax_media_sync_import_files', 'media_sync_import_files' );

/**
 * Ajax action to import selected file
 *
 * @since 0.1.0
 * @return void
 */
function media_sync_import_files() {
    MediaSync::media_sync_import_files();
}



add_action( 'plugins_loaded', 'media_sync_load_plugin_textdomain' );

/**
 * Loads plugin translated strings
 *
 * @since 0.1.4
 * @return void
 */
function media_sync_load_plugin_textdomain() {
    load_plugin_textdomain( 'media-sync', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}



add_action('restrict_manage_posts', 'media_sync_missing_media_library_filter');

/**
 * Add filter in Media Library for finding missing files
 *
 * @since 1.0.0
 * @return void
 */
function media_sync_missing_media_library_filter()
{
    require_once(ABSPATH . 'wp-admin/includes/screen.php');

    // Skip if it's not Media Library
    $scr = get_current_screen();
    if (!is_admin() || !is_object($scr) || property_exists($scr, 'base') !== true || $scr->base !== 'upload') return;

    // Get "missing files" filter from URL
    $missing = MediaSync::sanitize_input_string(INPUT_GET, 'media_sync_missing_files');
    ?>
    <label for="filter-by-media-sync-missing-files"
        class="screen-reader-text"><?= __('Filter by missing file', 'media-sync') ?></label>
    <select name="media_sync_missing_files" id="filter-by-media-sync-missing-files">
        <option
            value=""<?= !$missing ? ' selected="selected"' : '' ?>><?= __('Filter by missing file', 'media-sync') ?></option>
        <option
            value="no"<?= $missing === 'no' ? ' selected="selected"' : '' ?>><?= __('With file', 'media-sync') ?></option>
        <option
            value="yes"<?= $missing === 'yes' ? ' selected="selected"' : '' ?>><?= __('Without file', 'media-sync') ?></option>
    </select>
    <?php
}



add_action('pre_get_posts','media_sync_missing_media_library_apply_filter');

/**
 * Apply filter for Media Library items with or without missing files
 *
 * @since 1.0.0
 * @param $query
 * @return void
 */
function media_sync_missing_media_library_apply_filter($query)
{
    require_once(ABSPATH . 'wp-admin/includes/screen.php');

    // Skip if it's not Media Library
    $scr = get_current_screen();
    if (!is_admin() || !is_object($scr) || property_exists($scr, 'base') !== true || $scr->base !== 'upload') return;

    // Get "missing files" filter from URL
    $missing = MediaSync::sanitize_input_string(INPUT_GET, 'media_sync_missing_files');

    // Skip if "missing files" filter is not applied
    if (empty($missing)) {
        return;
    }

    // Prevent infinite loop
    remove_action('pre_get_posts', __FUNCTION__);

    // Get all media library items (to be able to check which ones do not have actual files)
    $media_library_items = get_posts(
        array_merge(
            $query->query,
            array(
                'fields' => 'ids',
                'posts_per_page' => -1
            )
        )
    );

    // Get missing files
    $missing_files = array();
    foreach ($media_library_items as $id) {
        // Get absolute path for this file
        $absolute_path = get_home_path() . str_replace(get_site_url(), "", wp_get_attachment_url($id));

        // Strip double slashes (//) in absolute path
        $absolute_path = preg_replace('/([^:])(\/{2,})/', '$1/', $absolute_path);

        // If file doesn't actually exists (if it's missing)
        if (!file_exists($absolute_path)) {
            $missing_files[] = $id;
        }
    }

    // Apply appropriate filter
    if ($missing === 'yes') {
        $query->set('post__in', !empty($missing_files) ? $missing_files : [0]);
    }

    if ($missing === 'no') {
        $query->set('post__not_in', $missing_files);
    }
}



/**
 * Main function for "Media Sync" page
 *
 * @since 0.1.0
 * @return void
 */
function media_sync_main_page() {
    MediaSync::media_sync_main_page();
}



/**
 * Render settings page
 *
 * @since 1.1.0
 * @return void
 */
function media_sync_options_page() {
    MediaSync::media_sync_options_page();
}



/**
 * Prepare plugin settings
 *
 * @since 1.1.0
 * @return void
 */
function media_sync_options_setup() {
    MediaSync::media_sync_options_setup();
}