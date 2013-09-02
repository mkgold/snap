<?php
/*
Plugin Name: Snap
Plugin URI: http://wordpress.org/plugins/snap
Description: Ultra simple photo sharing, for use with your favorite theme. WARNING: This takes over the entire site, currently rendering posts and pages useless.
Author: Helen Hou-Sandi | 10up
Version: 0.1
Author URI: http://profiles.wordpress.org/helen
License: MIT
License URI: http://opensource.org/licenses/MIT
*/

class Snap_Plugin {
	/**
	 * Set up hooks
	 */
	public function __construct() {
		add_filter( 'login_redirect', array( $this, 'login_redirect' ), 10, 3 );
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		add_action( 'add_attachment', array( $this, 'add_attachment' ) );
	}

	/**
	 * Kick users to upload when logging in.
	 * @param string $redirect_to Redirect location
	 * @param string $request Any redirect location passed via URL
	 * @param object $user Current user
	 * @return string Redirect location
	 */
	public function login_redirect( $redirect_to, $request, $user ) {
		return admin_url( 'media-new.php' );
	}

	/**
	 * Only show attachments in certain main queries.
	 *
	 * @param WP_Query $query Query object
	 * @return void
	 */
	public function pre_get_posts( $query ) {
		// only do this for the main loop on home and archive views. for now.
		if ( ! $query->is_main_query() || ( ! $query->is_home() && ! $query->is_archive() ) )
			return;

		$query->set( 'post_type', array( 'attachment' ) );
		$query->set( 'post_status', array( 'publish', 'inherit' ) );
	}

	/**
	 * Add the action to update the 'post_date' field on upload
	 *
	 * @param int $post_id The attachment ID
	 */
	public function add_attachment( $post_id ) {
		// only alter the publish date when first uploading
		add_filter( 'wp_update_attachment_metadata', array( $this, 'update_image_date' ), 10, 2 );
	}

	/**
	 * Update the 'post_date' field with the date in the image meta
	 *
	 * @param array $data The attachment meta array.
	 * @param int $post_id The attachment ID.
	 *
	 * @return array The image meta data array.
	 */
	function update_image_date( $data, $post_id ) {
		// no loops :)
		// we don't add this back because we only want it to run once per attachment
		remove_filter( 'wp_update_attachment_metadata', array( $this, 'update_image_date' ), 10, 2 );

		if ( isset( $data['image_meta']['created_timestamp'] ) ) {
			// Save the original date
			$original = get_post_field( 'post_date', $post_id );
			update_post_meta( $post_id, '_original_upload', $original );

			$post_array = array(
				'ID' => $post_id,
				'post_date' => gmdate( 'Y-m-d H:i:s', $data['image_meta']['created_timestamp'] ),
			);

			wp_update_post( $post_array );
		}

		return $data;
	}
}

$snap_plugin = new Snap_Plugin;