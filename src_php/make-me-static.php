<?php

/**
 * Plugin bootstrap file
 *
 * @link		https://madpenguin.uk
 * @since 		0.9.0
 * @package		make-me-static
 *
 * @wordpress-plugin
 * Plugin Name:       	Make Me Static
 * Plugin URI:        	https://madpenguin.uk/make-me-static
 * Description:       	Provide admin resources for the Make Me Static Crawler
 * Version:           	1.0.2
 * Requires at least: 	6.5
 * Requires PHP:      	7.0
 * Author:            	Mad Penguin Consulting Ltd
 * Author URI:        	https://madpenguin.uk/
 * License:           	GPL v2 or later
 * License URI:       	https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       	make-me-static
 * Domain Path:       	/languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'MM_STATIC_VERSION', '1.0.2' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function activate_plugin_name() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/mm-static-activator.php';
	mm_static_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function deactivate_plugin_name() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/mm-static-deactivator.php';
	mm_static_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_plugin_name' );
register_deactivation_hook( __FILE__, 'deactivate_plugin_name' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-mm-static.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    0.9.0
 */
function run_mm_static() {

	$plugin = new MM_Static();
	$plugin->run();

}
run_mm_static();

