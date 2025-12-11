<?php

namespace USPIN_WHEEL\Modules\SpinWheel;

defined( 'ABSPATH' ) || exit;

class Entries {
	private static $instance = null;

	/**
	 * Cache expiration time in seconds (60 minutes)
	 */
	const CACHE_EXPIRATION = 3600;

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'wp_ajax_ultimate_spin_wheel_get_entries', array( $this, 'get_entries' ) );
		add_action( 'wp_ajax_ultimate_spin_wheel_delete_entry', array( $this, 'delete_entry' ) );
		add_action( 'wp_ajax_ultimate_spin_wheel_bulk_delete_entries', array( $this, 'bulk_delete_entries' ) );
		add_action( 'wp_ajax_ultimate_spin_wheel_export_entries', array( $this, 'export_entries' ) );
		add_action( 'wp_ajax_ultimate_spin_wheel_clear_cache', array( $this, 'clear_cache' ) );
	}

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __clone() {
		_doing_it_wrong( __FUNCTION__, 'Cloning is forbidden.', '1.0.0' );
	}

	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, 'Unserializing instances of this class is forbidden.', '1.0.0' );
	}

	/**
	 * Sanitize filter parameters to prevent SQL injection
	 */
	private function sanitize_filter_params( &$item, $key ) {
		switch ( $key ) {
			case 'search':
				// Already sanitized with sanitize_text_field(), just ensure it's still a string
				$item = (string) $item;
				break;
			case 'status':
				// Only allow specific status values
				$allowed_statuses = array( 'active', 'inactive', 'pending', 'completed' );
				$item = in_array( $item, $allowed_statuses, true ) ? $item : '';
				break;
			case 'optin':
				// Only allow boolean-like values
				$allowed_optin = array( '0', '1', 'yes', 'no', 'true', 'false' );
				$item = in_array( $item, $allowed_optin, true ) ? $item : '';
				break;
			case 'page':
			case 'per_page':
				$item = max( 1, (int) $item ); // Ensure positive integers
				// Limit per_page to reasonable maximum
				if ( 'per_page' === $key ) {
					$item = min( $item, 1000 );
				}
				break;
			default:
				$item = null;
		}
	}

	public function get_entries() {

		global $wpdb;
		$table_name = $wpdb->prefix . 'wdengage_entries';

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimate_spin_wheel' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'ultimate-spin-wheel' ) ] );
		}

		// Sanitize all incoming parameters
		$filters = array(
			'page'     => isset( $_POST['page'] ) ? sanitize_text_field( wp_unslash( $_POST['page'] ) ) : 1,
			'per_page' => isset( $_POST['per_page'] ) ? sanitize_text_field( wp_unslash( $_POST['per_page'] ) ) : 20,
			'search'   => isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '',
			'status'   => isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '',
			'optin'    => isset( $_POST['optin'] ) ? sanitize_text_field( wp_unslash( $_POST['optin'] ) ) : '',
		);

		// Apply sanitization
		array_walk( $filters, array( $this, 'sanitize_filter_params' ) );

		// Extract sanitized values
		$page = max( 1, $filters['page'] );
		$per_page = max( 1, min( 1000, $filters['per_page'] ) ); // Ensure reasonable limits
		$search = $filters['search'];
		$status = $filters['status'];
		$optin = $filters['optin'];
		$clear_cache = isset( $_POST['clear_cache'] ) ? sanitize_text_field( wp_unslash( $_POST['clear_cache'] ) ) : '';

		// Generate cache key
		$cache_params = array(
			'page'     => $page,
			'per_page' => $per_page,
			'search'   => $search,
			'status'   => $status,
			'optin'    => $optin,
		);
		$cache_key = $this->get_cache_key( $cache_params );

		// Check if we should clear cache
		if ( 'true' === $clear_cache ) {
			$this->clear_entries_cache();
		}

		// Try to get from cache first
		$cached_result = get_transient( $cache_key );
		if ( false !== $cached_result && 'true' !== $clear_cache ) {
			wp_send_json_success( $cached_result );
			return;
		}

		// Build WHERE clause
		$where_conditions = array();
		$where_values = array();

		if ( ! empty( $search ) ) {
			$where_conditions[] = '(name LIKE %s OR email LIKE %s OR campaign_title LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $search ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		if ( ! empty( $status ) ) {
			$where_conditions[] = 'status = %s';
			$where_values[] = $status;
		}

		if ( ! empty( $optin ) ) {
			$where_conditions[] = 'optin = %s';
			$where_values[] = $optin;
		}

		$where_clause = '';
		if ( ! empty( $where_conditions ) ) {
			$where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
		}

		// Get total count
		if ( ! empty( $where_values ) ) {
			$count_sql = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'wdengage_entries ' . $where_clause;
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$total = $wpdb->get_var( $wpdb->prepare( $count_sql, $where_values ) );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wdengage_entries" );
		}

		// Calculate pagination
		$total_pages = ceil( $total / $per_page );
		$offset = ( $page - 1 ) * $per_page;

		// Get entries
		$query_values = array_merge( $where_values, array( $per_page, $offset ) );
		$entries_sql = 'SELECT * FROM ' . $wpdb->prefix . 'wdengage_entries ' . $where_clause . ' ORDER BY created_at DESC LIMIT %d OFFSET %d';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$entries = $wpdb->get_results( $wpdb->prepare( $entries_sql, $query_values ) );

		$result = array(
			'entries'      => $entries,
			'total'        => intval( $total ),
			'total_pages'  => intval( $total_pages ),
			'current_page' => $page,
			'per_page'     => $per_page,
		);

		// Cache the result for 60 minutes
		set_transient( $cache_key, $result, self::CACHE_EXPIRATION );

		wp_send_json_success( $result );
	}

	public function delete_entry() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimate_spin_wheel' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'ultimate-spin-wheel' ) ] );
		}

		$id = isset( $_POST['id'] ) ? intval( wp_unslash( $_POST['id'] ) ) : 0;
		if ( ! $id ) {
			wp_send_json_error( 'Invalid entry ID' );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'wdengage_entries';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete( $table_name, array( 'id' => $id ), array( '%d' ) );

		if ( false === $result ) {
			wp_send_json_error( 'Failed to delete entry' );
		}

		// Clear cache since data has changed
		$this->clear_entries_cache();

		wp_send_json_success( 'Entry deleted successfully' );
	}

	public function bulk_delete_entries() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimate_spin_wheel' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'ultimate-spin-wheel' ) ] );
		}

		$ids = isset( $_POST['ids'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['ids'] ) ) : array();
		// Sanitize each id
		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}
		if ( empty( $ids ) ) {
			wp_send_json_error( 'No entries selected' );
		}

		// Sanitize IDs
		$ids = array_map( 'intval', $ids );
		$ids = array_filter( $ids, function( $id ) {
			return $id > 0;
		} );

		if ( empty( $ids ) ) {
			wp_send_json_error( 'Invalid entry IDs' );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'wdengage_entries';

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$delete_sql = 'DELETE FROM ' . $wpdb->prefix . 'wdengage_entries WHERE id IN (' . $placeholders . ')';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->query( $wpdb->prepare( $delete_sql, $ids ) );

		if ( false === $result ) {
			wp_send_json_error( 'Failed to delete entries' );
		}

		// Clear cache since data has changed
		$this->clear_entries_cache();

		wp_send_json_success( sprintf( '%d entries deleted successfully', $result ) );
	}

	public function export_entries() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimate_spin_wheel' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'ultimate-spin-wheel' ) ] );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'wdengage_entries';

		// Sanitize search parameter
		$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

		$where_conditions = array();
		$where_values = array();

		if ( ! empty( $search ) ) {
			$where_conditions[] = '(name LIKE %s OR email LIKE %s OR campaign_title LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $search ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		// Always filter out empty emails for export
		$where_conditions[] = 'email != %s';
		$where_values[] = '';

		$where_clause = '';
		if ( ! empty( $where_conditions ) ) {
			$where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
		}

		if ( ! empty( $where_values ) ) {
			$export_sql = 'SELECT * FROM ' . $wpdb->prefix . 'wdengage_entries ' . $where_clause . ' ORDER BY created_at DESC';
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$entries = $wpdb->get_results( $wpdb->prepare( $export_sql, $where_values ) );
		} else {
			// This case should not happen since we always have the email condition
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$entries = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wdengage_entries WHERE email != '' ORDER BY created_at DESC" );
		}
		if ( empty( $entries ) ) {
			wp_send_json_error( 'No entries found for export' );
		}

		// Prepare CSV data
		$csv_data = array();
		$csv_data[] = array( 'ID', 'Name', 'Email', 'Campaign ID', 'Campaign Title', 'Created At' );
		foreach ( $entries as $entry ) {
			$csv_data[] = array(
				$entry->id,
				$entry->name,
				$entry->email,
				$entry->campaign_id,
				$entry->campaign_title,
				$entry->created_at,
			);
		}

		// Build CSV string
		$csv_string = '';
		foreach ( $csv_data as $row ) {
			$escaped_row = array();
			foreach ( $row as $field ) {
				// Escape double quotes and wrap in quotes if necessary
				$field = str_replace( '"', '""', $field );
				if ( strpos( $field, ',' ) !== false || strpos( $field, '"' ) !== false || strpos( $field, "\n" ) !== false ) {
					$field = '"' . $field . '"';
				}
				$escaped_row[] = $field;
			}
			$csv_string .= implode( ',', $escaped_row ) . "\n";
		}

		// Return as JSON for AJAX
		wp_send_json_success( $csv_string );
	}

	/**
	 * Generate cache key for entries
	 */
	private function get_cache_key( $params = array() ) {
		$key_parts = array(
			'ultimate_spin_wheel_entries',
			isset( $params['page'] ) ? $params['page'] : 1,
			isset( $params['per_page'] ) ? $params['per_page'] : 20,
			isset( $params['search'] ) ? $params['search'] : '',
			isset( $params['status'] ) ? $params['status'] : '',
			isset( $params['optin'] ) ? $params['optin'] : '',
		);
		return md5( implode( '_', $key_parts ) );
	}

	/**
	 * Clear all cache for entries
	 */
	private function clear_entries_cache() {
		// Clear transients that start with our prefix
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ultimate_spin_wheel_%'" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_ultimate_spin_wheel_%'" );
	}

	/**
	 * Clear cache AJAX handler
	 */
	public function clear_cache() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimate_spin_wheel' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'ultimate-spin-wheel' ) ] );
		}

		$this->clear_entries_cache();
		wp_send_json_success( [ 'message' => esc_html__( 'Cache cleared successfully', 'ultimate-spin-wheel' ) ] );
	}

	/**
	 * Verify nonce for security
	 */
	private function verify_nonce() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimate_spin_wheel' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid nonce', 'ultimate-spin-wheel' ) ) );
		}
	}
}
