<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once plugin_dir_path( __FILE__ ) . '../includes/make-me-static-activator.php';

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://madpenguin.uk
 * @since      0.9.0
 *
 * @package    make_me_static
 * @subpackage make_me_static/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    make_me_static
 * @subpackage make_me_static/admin
 * @author     Gareth Bult <gareth@madpenguin.uk>
 */
class make_me_static_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    0.9.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    0.9.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The default directory address
	 *
	 * @since    1.0.32
	 * @access   private
	 * @var      string    $directory	The default directory address
	 * 
	 * This plugin uses an external service hosted by Mad Penguin Consulting Ltd.
	 * Details are contained in the README.md file in this repository. In this instance
	 * $directory should point to Mad Penguin's directory server, which should always
	 * be of the form "https://mms-directory-(n).madpenguin.uk/"
	 *  
	 */

	private $directory = 'https://mms-directory-1.madpenguin.uk/';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.9.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		//
		add_action( 'admin_menu'        		, 'make_me_static_setup_menu', 9999);
		add_action( 'save_post'         		, 'make_me_static_flag_change', 11, 0 );
		add_action( 'deleted_post'         		, 'make_me_static_flag_change', 11, 0 );
		add_action( 'add_category'   			, 'make_me_static_flag_change', 11, 0 );
		add_action( 'delete_category'  			, 'make_me_static_flag_change', 11, 0 );
		add_action( 'comment_post'   			, 'make_me_static_flag_change', 11, 0 );
		add_action( 'edit_comment'   			, 'make_me_static_flag_change', 11, 0 );
		add_action( 'wp_delete_comment'			, 'make_me_static_flag_change', 11, 0 );
		add_action( 'upgrader_process_complete'	, 'make_me_static_upgrade_complete', 11, 0 );

		/**
		 * Register that we've upgraded the plugin
		 *
		 * @since      1.0.58
		 */

		function make_me_static_upgrade_complete() {
			make_me_static_Activator::upgrade();	
			update_option ('make-me-static-change', new DateTime());
		}

		/**
		 * Register that the site is now potentially out of sync
		 *
		 * @since      1.0.58
		 */

		function make_me_static_flag_change() {
			update_option ('make-me-static-change', new DateTime());
		}

		/**
		 * Register a new menu optoin for the Admin page
		 *
		 * @since      1.0.58
		 */

		function make_me_static_setup_menu(){
			add_menu_page( 'Make Me Static', 'MakeMeStatic', 'manage_options', 'make-me-static', 'make_me_static_init' );
			if (!get_option ('make-me-static-uuid', false)) {
				add_option ('make-me-static-uuid', uniqid('mms_'));
			}
		}

		/**
		 * Output the place-holder for the admin page template
		 *
		 * @since      1.0.58
		 */

		function make_me_static_init() {
			echo "<div class='mm-static-main'><div id='make-me-static-directory'></div></div>";
		}

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    0.9.0
	 */
	public function enqueue_styles() {
		if (get_current_screen()->base == 'toplevel_page_make-me-static') {
			wp_enqueue_style( $this->plugin_name.'-fonts', "https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap", array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name.'-admin', plugin_dir_url( __FILE__ ) . 'css/make-me-static-admin.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name.'-theme', plugin_dir_url( __FILE__ ) . 'css/make-me-static-theme.css', array(), $this->version, 'all' );		
		}
	}

	/**
	 * Register the JavaScript for the admin area, only load our resources if we are
	 * on our own admin page so we can't interfere with any other pages.
	 *
	 * @since    0.9.0
	 */
	public function enqueue_scripts() {
		if (get_current_screen()->base == 'toplevel_page_make-me-static') {
			$path = str_contains( $this->directory, '-dev') ? $this->directory.'src/main.js?ver='.$this->version : '/wp-content/plugins/make-me-static/admin/js/index.js?ver='.$this->version;
			$my_id = $this->plugin_name.'-directory';
			wp_enqueue_script( $my_id, $path, array(), $this->version);
			add_filter('script_loader_tag', function($tag, $handle) use ($my_id) {
				if ( $handle === $my_id) {
					$tag = str_replace( '<script ', '<script type="module" ', $tag );
				}
				return $tag;
			}, 10, 2);
			wp_localize_script( $my_id, 'MMS_API_Settings',
				array( 
					'apiurl' => esc_url_raw( rest_url() ),
					'nonce' => wp_create_nonce( 'wp_rest' ),
					'uuid' => get_option ('make-me-static-uuid', false),
					'user' => wp_get_current_user()->ID
				)
			);
		}
	}
}
