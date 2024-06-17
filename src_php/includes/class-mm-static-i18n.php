<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://madpenguin.uk
 * @since      0.9.0
 *
 * @package    mm_static
 * @subpackage mm_static/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      0.9.0
 * @package    mm_static
 * @subpackage mm_static/includes
 * @author     Gareth Bult <gareth@madpenguin.uk>
 */
class mm_static_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    0.9.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'mm-static',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}
