<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * 
 * This Class defines our JSON API
 *
 * @since      1.0.2
 * @package    make-me-static-api
 *
 */

class make_me_static_api {

    /**
     * 
     *  @since  1.0.2
     *  @access public
     * 
     */

	/**
	 * How we present the API
	 *
	 * @since    1.0.2
	 * @access   private
	 * @var      string    $mount_point    The base URL for our API
	 */

	private $mount_point = 'make_me_static/v1';

	/**
	 * Plugin naming
	 *
	 * @since    1.0.2
	 * @access   private
	 * @var      string    $plugin_name    The name of our plugin
	 */

	private $plugin_name;

	/**
	 * Plugin version
	 *
	 * @since    1.0.2
	 * @access   private
	 * @var      string    $plugin_name    The version of our plugin
	 */

	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.2
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version   		The version of this plugin.
	 */

    public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		//
        //  Private, requires a Nonce / working browser admin session
        //
		foreach (array( 'register_host') as $route) {
			register_rest_route( $this->mount_point, '/'.$route , array(
				'methods' => 'GET',
				'permission_callback' => array($this, 'is_access_allowed'),
				'callback' => array($this, 'api_'.$route)
			));
		};
		//
		//	Public Routes, only for validating outside data
		//
		foreach (array( 'validate_host', 'notify_changes') as $route) {
			register_rest_route( $this->mount_point, '/'.$route , array(
				'methods' => 'GET',
				'permission_callback' => '__return_true',
				'callback' => array($this, 'api_'.$route)
			));
		};
	}

	/**
	 * 
	 * 	update_metadata - record the session token against our host_id in the
	 *  metadata for this user. Expire any old host_id's for which the session
	 *  token has expired or is no longer valid.
	 * 
	 * @since		1.0.3
	 * @access   	private
	 * @param       string    $host_id       The Make_Me_Static host_id
	 * 
	 */

	private function update_metadata ( $host_id ) {
		//
		//	Get the array of host_id's for this user
		//
		$user_id = wp_get_current_user()->ID;
		$session_token = wp_get_session_token();
		$meta = get_user_meta ( $user_id , 'make_me_static_host_ids' );
		if ($meta)
			$meta = $meta[0];
		else
			$meta = array();
		//
		//	Clean any expired sessions
		//
		$manager = WP_Session_Tokens::get_instance( $user_id );
		$meta = array_filter( $meta, function( $token ) use ( $manager ) {
			return $manager->verify( $token );
		});
		//
		//	Update the stamp and save ...
		//
		$meta[$host_id] = $session_token;
		update_user_meta ( $user_id, 'make_me_static_host_ids', $meta );
	}

	/**
	 * 
	 * 	Determine is the host_id has previously been validated for this user_id
	 * 
	 * @since		1.0.3
	 * @access   	private
	 * @param       string    $user_id       The Wordpress user_id
	 * @param       string    $host_id       The Make_Me_Static host_id
	 * @return    	boolean   				 Whether the user/host combination is currently valid
	 * 
	 */

	private function is_host_id_valid ( $user_id, $host_id ) {
		$meta = get_user_meta ( $user_id , 'make_me_static_host_ids' );
		if ($meta)
			$meta = $meta[0];
		else
			$meta = array();
		if (!isset($meta[$host_id])) {
			// error_log ('Host ID invalid: '.$host_id);
			return false;
		}
		$manager = WP_Session_Tokens::get_instance( $user_id );
		if (!$manager->verify( $meta[$host_id] )) {
			// error_log ('Session token invalid: '.$meta[$host_id]);
			return false;
		}
		return true;
	}

	/**
	 * 
	 * 	Simple callback to check that the current user is an administrator
	 * 
	 * @since		1.0.3
	 * @access   	private
	 * @param       WP_REST_Request $request  The incoming REQUEST object
	 * @return    	boolean   				  Whether the current user is allowed to use the API call
	 * 
	 */

	public function is_access_allowed ( WP_REST_Request $request ) {
		return current_user_can('administrator');
	}

	/**
	 * Register the current session. MMS identifies users via "host_id" which is 
	 * negotiated via a public key encryptione exchange between the app and MMS.
	 * Once the app has a valid HOST_ID it stores it here so MMS can make sure
	 * it's session was initiated my an admin user for this site. Effectively a
	 * form of MMS nonse.
	 *
	 * @since		1.0.2
	 * @access   	public
	 * @param       WP_REST_Request $request  The incoming REQUEST object
	 * @return    	WP_REST_Response 		  200 if Ok or 403 if unauthorized
	 * 
	 */

	public function api_register_host ( WP_REST_Request $request ) {
		//
		//	Incoming parameters include the site (a uuid) and host_id
		//
		$site = sanitize_text_field($request->get_param( 'site' ));
		//
		//	Make sure this request is for us ...
		//
		if (get_option ('make-me-static-uuid', false) != $site)
			return new WP_REST_Response( array( 'message' => 'Request was for the wrong domain: '.$site ), 403);
		//
		//	Make sure host_id is available and update it's stamp if it already exists
		//
		$this->update_metadata ( sanitize_text_field($request->get_param( 'host_id' )));
		//
		//	Also check the permalink structure is ok
		//
		$permalink_structure = get_option( 'permalink_structure' );
		$result = empty($permalink_structure) || str_starts_with ($permalink_structure, '/index.php') ? 'plain' : 'ok';
		//
		return new WP_REST_Response( array( 'message' => 'Ok, session registered', 'permalink' => $result ), 200);
	}

	/**
	 * Validation service for MMS to use.
	 * 
	 * MMS Will send a UUID, USER and HOST_ID, we're just going to look it up in our
	 * metadata and return whether the HOST_ID is valid on this site a current admin session.
	 * It's effectively an anonymous service so no user restrictions or nonce's apply.
	 *
	 * @since		1.0.0
	 * @access   	private
	 * @param       WP_REST_Request $request  The incoming REQUEST object
	 * @return    	WP_REST_Response 		  200 if Ok or 403 if unauthorized
	 * 
	 */

	public function api_validate_host ( WP_REST_Request $request ) {
		//
		//	Incoming parameters include the site (a uuid) and host_id and user
		//
		$site = sanitize_text_field($request->get_param( 'site' ));
		//
		//	Make sure this request is for us ...
		//
		if (get_option ('make-me-static-uuid', false) != $site)
			return new WP_REST_Response( array( 'message' => 'Request was for the wrong domain: '.$site ), 403);
		//
		//	Check the metadata to see if there is a valid session for this user/host_id
		//
		if ($this->is_host_id_valid (sanitize_text_field($request->get_param( 'user' )), sanitize_text_field($request->get_param( 'host_id' ))))
			return new WP_REST_Response( array( 'message' => 'Ok, host_id attached to a valid session' ), 200);
		return new WP_REST_Response( array( 'message' => 'Session invalid or expired' ), 403);
	}

	/**
	 * Report whether there are changes outstanding
	 *
	 * @since		0.9.67
	 * @access   	private
	 * @return    	array 		  Date of last change and date of last sitemap update
	 * 
	 */

	public function api_notify_changes () {
		$last_change = get_option ('make-me-static-change', (new DateTime())->setTimestamp(1))->format('c');
		$last_sitemap = get_option ('make-me-static-last', (new DateTime())->setTimestamp(0))->format('c');
		return array(
			'last_change' => $last_change,
			'last_sitemap' => $last_sitemap
		);
	}
}