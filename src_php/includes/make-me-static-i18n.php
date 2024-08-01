<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://madpenguin.uk
 * @since      0.9.0
 *
 * @package    make_me_static
 * @subpackage make_me_static/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      0.9.0
 * @package    make_me_static
 * @subpackage make_me_static/includes
 * @author     Gareth Bult <gareth@madpenguin.uk>
 */
class make_me_static_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    0.9.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'make-me-static',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}
