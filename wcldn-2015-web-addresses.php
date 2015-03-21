<?php

/*
 * Plugin Name: WCLDN 2015 – Web Addresses Talk, demo plugin
 * License: GPL2+
 * Author: <a href="http://www.simonwheatley.co.uk/">Simon Wheatley</a>
 */

/**
 * This is some demo code associated with Simon Wheatley's
 * presentation on WordPress web addresses at
 * WordCamp London 2015
 *
 * @package SW_WCLDN_2015
 */
class SW_WCLDN_2015 {

	/**
	 * This method deals with creating the singleton,
	 * and returning it.
	 *
	 * @access @static
	 *
	 * @return  object
	 */
	static public function init() {
		static $instance = false;

		if ( ! $instance )
			$instance = new SW_WCLDN_2015;

		return $instance;

	}

	/**
	 * The constructor for this little class. Here we add actions
	 * and filters.
	 */
	public function __construct() {
		add_action( 'init',       array( $this, 'action_init' ) );
		add_filter( 'query_vars', array( $this, 'filter_query_vars' ) );
		add_filter( 'request',    array( $this, 'filter_request' ) );
	}

	/**
	 * In order for a custom rewrite rule to work, we
	 * must inform WordPress of any query variables it
	 * references in the rewrite rule regex. We do
	 * that by filtering `query_vars`.
	 *
	 * @param $query_vars
	 *
	 * @return array
	 */
	public function filter_query_vars( $query_vars ) {
		$query_vars[] = 'sw_compare';
		return $query_vars;
	}

	/**
	 * We filter the request as part of:
	 * * The JSON endpoint
	 * * The custom compare requests
	 *
	 * @param $vars
	 *
	 * @return mixed
	 */
	public function filter_request( $vars ) {
		if ( ! is_admin() ) {
			$this->fixup_json_endpoint_requests( $vars );
			$this->parse_compare_request( $vars );
		}
		return $vars;
	}

	/**
	 * On the init action we call the method
	 * to create the client custom post type.
	 *
	 * @return void
	 **/
	public function action_init() {
		$this->init_clients();
	}

	/**
	 * This method:
	 * * Creates the client custom post type
	 * * Adds the JSON endpoint
	 * * Adds a custom rewrite rule to compare clients
	 *
	 * @return void
	 **/
	protected function init_clients() {

		$labels = array(
			'name'          => 'Clients',
			'singular_name' => 'Client',
			'add_new_item'  => 'Add New Client',
		);

		$rewrite = array(
			'slug'  => 'work-for',
			'feeds' => false,
			'pages' => false,
		);

		$args = array(
			'labels'             => $labels,
			'has_archive'        => 'our-clients',
			'public'             => true,
			'menu_icon'          => 'dashicons-businessman',
			'rewrite'            => $rewrite,
		);
		register_post_type( 'sw_client', $args );

		add_rewrite_endpoint( 'json', EP_PAGES, 'sw_json' );

		add_rewrite_rule( 'compare/([^/]+)/?$', 'index.php?sw_compare=$matches[1]' );
	}

	/**
	 * WordPress Endpoints expect to pass a value, but our
	 * JSON endpoint does not need to, so we set `sw_json`
	 * to true if it is present.
	 *
	 * @param array $vars The request variables (passed by reference)
	 */
	protected function fixup_json_endpoint_requests( $vars ) {
		if( isset( $vars['sw_json'] ) && isset( $vars['sw_client'] ) ) {
			$vars['sw_json'] = true;
		} else {
			unset( $vars['sw_json'] );
		}
	}

	/**
	 * The "Compare" custom rewrite rule has created an entirely new
	 * type of request, outside of any normal WordPress query. Here we
	 * set the vars for the WordPress main query to make the request
	 * we require.
	 *
	 * @param array $vars The request variables (passed by reference)
	 */
	protected function parse_compare_request( & $vars ) {
		if( ! isset( $vars['sw_compare'] ) ) {
			return;
		}
		$names_to_compare = explode( '+', $vars['sw_compare'] );
		// There is currently no way to use a single WP_Query
		// to retrieve multiple posts by name, i.e. you cannot
		// pass an array of names to the `name` parameter
		// of WP_Query.
		$post_ids = array();
		// If this code was going into production, we might
		// want to use object cache to store the resultant
		// $post_ids array in production, and avoid running
		// the multiple queries in the loop below.
		foreach ( $names_to_compare as $name ) {
			$args = array(
				'post_type' => 'sw_client',
				'name'      => $name,
				'fields'    => 'ids',
			);
			$query    = new WP_Query( $args );
			$post_ids = array_merge( $post_ids, $query->posts );
		}
		$vars['post__in']  = $post_ids;
		$vars['post_type'] = 'sw_client';
	}

}

SW_WCLDN_2015::init();

