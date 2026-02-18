<?php

namespace USPIN_WHEEL\Includes\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Campaign Management Class
 * Handles campaign operations like duplication
 */
class Campaigns {

	private static $instance = null;

	/**
	 * Get singleton instance
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - Register AJAX handlers
	 */
	private function __construct() {
		add_action( 'wp_ajax_uspw_duplicate_campaign', [ $this, 'duplicate_campaign_ajax' ] );
	}

	/**
	 * AJAX handler for campaign duplication
	 */
	public function duplicate_campaign_ajax() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimate_spin_wheel' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid security token', 'ultimate-spin-wheel' ) ] );
		}

		// Check user permissions
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Insufficient permissions', 'ultimate-spin-wheel' ) ] );
		}

		// Get campaign ID
		$campaign_id = isset( $_POST['campaign_id'] ) ? absint( wp_unslash( $_POST['campaign_id'] ) ) : 0;
		if ( ! $campaign_id ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid campaign ID', 'ultimate-spin-wheel' ) ] );
		}

		// Verify the post exists and is the correct type
		$original_post = get_post( $campaign_id );
		if ( ! $original_post || $original_post->post_type !== 'wowdevs_engage' ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Campaign not found', 'ultimate-spin-wheel' ) ] );
		}

		// Perform duplication
		$new_campaign_id = $this->duplicate_campaign( $campaign_id );

		if ( is_wp_error( $new_campaign_id ) ) {
			wp_send_json_error( [ 'message' => $new_campaign_id->get_error_message() ] );
		}

		wp_send_json_success([
			'message'         => esc_html__( 'Campaign duplicated successfully', 'ultimate-spin-wheel' ),
			'new_campaign_id' => $new_campaign_id,
		]);
	}

	/**
	 * Duplicate a campaign with all its metadata
	 *
	 * @param int $campaign_id Original campaign ID
	 * @return int|WP_Error New campaign ID or error
	 */
	public function duplicate_campaign( $campaign_id ) {
		// Get original post
		$original_post = get_post( $campaign_id );
		if ( ! $original_post ) {
			return new \WP_Error( 'invalid_campaign', __( 'Original campaign not found', 'ultimate-spin-wheel' ) );
		}

		// Prepare new post data
		$new_post_data = [
			'post_title'   => $original_post->post_title . ' (Copy)',
			'post_content' => $original_post->post_content,
			'post_status'  => 'publish',
			'post_type'    => $original_post->post_type,
			'post_author'  => get_current_user_id(),
		];

		// Insert new post
		$new_campaign_id = wp_insert_post( $new_post_data );

		if ( is_wp_error( $new_campaign_id ) ) {
			return $new_campaign_id;
		}

		// Get all metadata from original campaign
		$meta_data = get_post_meta( $campaign_id );

		// Copy all metadata to new campaign
		foreach ( $meta_data as $meta_key => $meta_values ) {
			// Skip protected meta keys
			if ( substr( $meta_key, 0, 1 ) === '_' ) {
				continue;
			}

			// Copy each meta value (there can be multiple values for the same key)
			foreach ( $meta_values as $meta_value ) {
				// Unserialize if needed
				$meta_value = maybe_unserialize( $meta_value );
				add_post_meta( $new_campaign_id, $meta_key, $meta_value );
			}
		}

		// Reset impression count for the new campaign
		update_post_meta( $new_campaign_id, 'uspw_impressions_count', 0 );

		return $new_campaign_id;
	}
}
