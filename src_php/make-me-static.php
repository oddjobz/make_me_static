<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/make-me-static.php';

/** 
 * Activator and de-activator hooks from the standard plugin template
 * 
 */

require_once plugin_dir_path( __FILE__ ) . 'includes/make-me-static-activator.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/make-me-static-deactivator.php';

/**
 * Currently plugin version.
 */

define( 'MAKE_ME_STATIC_VERSION', '1.1.45' );

/**
 * Plugin bootstrap file
 *
 * @link		https://madpenguin.uk
 * @since 		0.9.0
 * @package		make-me-static
 *
 * @wordpress-plugin
 * Plugin Name:       	Make Me Static, Static Site Generator, Git, Pages and Live Stats
 * Plugin URI:        	https://madpenguin.uk/make-me-static
 * Description:       	Provide admin resources for the Make Me Static Crawler
 * Version:           	1.1.45
 * Requires at least: 	6.5
 * Requires PHP:      	7.0
 * Author:            	Mad Penguin Consulting Ltd
 * Author URI:        	https://madpenguin.uk/
 * License:           	GPL v2 or later
 * License URI:       	https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       	make-me-static
 * Domain Path:       	/languages
 * 
 * 	This Plugin employs the following third party libraries;
 *  - https://github.com/composer/composer (MIT)
 *  - https://github.com/icamys/php-sitemap-generator (MIT)
 * 
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function make_me_static_activate() {
	make_me_static_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-plugin-name-deactivator.php
 */
function make_me_static_deactivate() {
	make_me_static_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'make_me_static_activate' );
register_deactivation_hook( __FILE__, 'make_me_static_deactivate' );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    0.9.0
 */
function make_me_static_run () {

	$plugin = new Make_Me_Static_Main();
	$plugin->run();

}
make_me_static_run ();

