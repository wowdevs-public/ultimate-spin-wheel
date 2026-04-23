<?php

namespace USPIN_WHEEL\Includes\Core;

defined( 'ABSPATH' ) || exit;

class Entries {
	/**
	 * Singleton instance
	 */
	private static $instance = null;

	/**
	 * Cache expiration time in seconds (60 minutes)
	 */
	const CACHE_EXPIRATION = 3600;

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'wp_ajax_ultimate_spin_wheel_get_entries', [ $this, 'get_entries' ] );
		add_action( 'wp_ajax_ultimate_spin_wheel_delete_entry', [ $this, 'delete_entry' ] );
		add_action( 'wp_ajax_ultimate_spin_wheel_bulk_delete_entries', [ $this, 'bulk_delete_entries' ] );
		add_action( 'wp_ajax_ultimate_spin_wheel_export_entries', [ $this, 'export_entries' ] );
		add_action( 'wp_ajax_ultimate_spin_wheel_clear_cache', [ $this, 'clear_cache' ] );
		// add_action( 'wp_ajax_ultimate_spin_wheel_get_entry_campaigns', [ $this, 'get_campaigns' ] ); // Moved to Pro
		add_action( 'wp_ajax_ultimate_spin_wheel_update_entry_status', [ $this, 'update_entry_status' ] );
		// add_action( 'wp_ajax_ultimate_spin_wheel_update_entry_notes', [ $this, 'update_entry_notes' ] ); // Moved to Pro
		add_action( 'wp_ajax_ultimate_spin_wheel_block_identity', [ $this, 'block_identity' ] );
		add_action( 'wp_ajax_ultimate_spin_wheel_unblock_identity', [ $this, 'unblock_identity' ] );
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
				$item = (string) $item;
				break;
			case 'campaign_id':
				$item = (int) $item;
				break;
			case 'win_status':
				$allowed_winners = [ 'wins', 'lost' ];
				$item            = in_array( $item, $allowed_winners, true ) ? $item : '';
				break;
			case 'start_date':
			case 'end_date':
				// Sanitize date string
				$item = sanitize_text_field( $item );
				// Basic date format validation (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)
				if ( ! empty( $item ) && ! preg_match( '/^\d{4}-\d{2}-\d{2}(\s\d{2}:\d{2}:\d{2})?$/', $item ) ) {
					$item = '';
				}
				break;
			case 'per_page':
			case 'page':
				$item = max( 1, (int) $item ); // Ensure positive integers
				// Limit per_page to reasonable maximum
				if ( 'per_page' === $key ) {
					$item = min( $item, 1000 );
				}
				break;
			case 'lead_status':
				$item = sanitize_text_field( $item );
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
		$filters = [
			'page'        => isset( $_POST['page'] ) ? sanitize_text_field( wp_unslash( $_POST['page'] ) ) : 1,
			'per_page'    => isset( $_POST['per_page'] ) ? sanitize_text_field( wp_unslash( $_POST['per_page'] ) ) : 20,
			'search'      => isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '',
			'campaign_id' => isset( $_POST['campaign_id'] ) ? sanitize_text_field( wp_unslash( $_POST['campaign_id'] ) ) : '',
			'win_status'  => isset( $_POST['win_status'] ) ? sanitize_text_field( wp_unslash( $_POST['win_status'] ) ) : '',
			'start_date'  => isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '',
			'end_date'    => isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : '',
			'lead_status' => isset( $_POST['lead_status'] ) ? sanitize_text_field( wp_unslash( $_POST['lead_status'] ) ) : '',
		];

		// Apply sanitization
		array_walk( $filters, [ $this, 'sanitize_filter_params' ] );

		// Extract sanitized values
		$page        = max( 1, $filters['page'] );
		$per_page    = max( 1, min( 1000, $filters['per_page'] ) );
		$search      = $filters['search'];
		$campaign_id = $filters['campaign_id'];
		$win_status  = $filters['win_status'];
		$start_date  = $filters['start_date'];
		$end_date    = $filters['end_date'];
		$lead_status = $filters['lead_status'];

		// NOTE: Caching removed intentionally - causes stale data issues with paginated entries
		// Each request now fetches fresh data directly from the database

		// Build WHERE clause
		$where_conditions = [];
		$where_values     = [];

		if ( ! empty( $search ) ) {
			$where_conditions[] = '(name LIKE %s OR email LIKE %s OR phone LIKE %s OR campaign_title LIKE %s OR others_data LIKE %s)';
			$search_term        = '%' . $wpdb->esc_like( $search ) . '%';
			$where_values[]     = $search_term;
			$where_values[]     = $search_term;
			$where_values[]     = $search_term;
			$where_values[]     = $search_term;
			$where_values[]     = $search_term;
		}

		if ( ! empty( $campaign_id ) ) {
			$where_conditions[] = 'campaign_id = %d';
			$where_values[]     = $campaign_id;
		}

		if ( ! empty( $win_status ) ) {
			if ( 'wins' === $win_status ) {
				$where_conditions[] = '(others_data LIKE %s OR (others_data LIKE %s AND others_data NOT LIKE %s))';
				$where_values[]     = '%"status":"wins"%';
				$where_values[]     = '%"coupon_code":"%';
				$where_values[]     = '%"coupon_code":""%';
			} else {
				$where_conditions[] = 'others_data LIKE %s AND others_data LIKE %s';
				$where_values[]     = '%"status":"lost"%';
				$where_values[]     = '%"coupon_code":""%';
			}
		}

		if ( ! empty( $start_date ) ) {
			// Convert Local Start Date to UTC for DB Query
			$utc_start = get_gmt_from_date( $start_date . ' 00:00:00' );
			$where_conditions[] = 'created_at >= %s';
			$where_values[]     = $utc_start;
		}

		if ( ! empty( $end_date ) ) {
			// Convert Local End Date to UTC for DB Query
			$utc_end = get_gmt_from_date( $end_date . ' 23:59:59' );
			$where_conditions[] = 'created_at <= %s';
			$where_values[]     = $utc_end;
		}

		if ( ! empty( $lead_status ) && 'all' !== $lead_status ) {
			if ( 'trash' === $lead_status ) {
				$where_conditions[] = 'status LIKE %s';
				$where_values[]     = '%"stage":"trash"%';
			} else {
				// Filter by stage in JSON. If status is empty/legacy and we want 'new', handle it.
				if ( 'new' === $lead_status ) {
					$where_conditions[] = '(status LIKE %s OR status IS NULL OR status = "" OR status NOT LIKE %s)';
					$where_values[]     = '%"stage":"new"%';
					$where_values[]     = '%"stage":%';
				} else {
					$where_conditions[] = 'status LIKE %s';
					$where_values[]     = '%"stage":"' . $wpdb->esc_like( $lead_status ) . '"%';
				}
				// Always exclude trash from non-trash views
				$where_conditions[] = '(status NOT LIKE %s OR status IS NULL)';
				$where_values[]     = '%"stage":"trash"%';
			}
		} else {
			// By default, exclude trash if no specific status is requested
			$where_conditions[] = '(status NOT LIKE %s OR status IS NULL)';
			$where_values[]     = '%"stage":"trash"%';
		}

		$where_clause = '';
		if ( ! empty( $where_conditions ) ) {
			$where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
		}

		// Get total count
		if ( ! empty( $where_values ) ) {
			$count_sql = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'wdengage_entries ' . $where_clause;
			$total     = $wpdb->get_var( $wpdb->prepare( $count_sql, $where_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		} else {
			$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wdengage_entries" );
		}

		// Calculate pagination
		$total_pages = ceil( $total / $per_page );
		$offset      = ( $page - 1 ) * $per_page;

		// Get entries
		$query_values = array_merge( $where_values, [ $per_page, $offset ] );
		$entries_sql  = 'SELECT * FROM ' . $wpdb->prefix . 'wdengage_entries ' . $where_clause . ' ORDER BY created_at DESC LIMIT %d OFFSET %d';
		$entries      = $wpdb->get_results( $wpdb->prepare( $entries_sql, $query_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		// Format dates according to WordPress settings (Format + Timezone + Translation)
		// Doing this here only for the current page (e.g. 20-50 items) is extremely fast.
		$wp_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

		foreach ( $entries as $entry ) {
			if ( ! empty( $entry->created_at ) ) {
				// created_at is UTC in DB, wp_date() with null timestamp uses current site time,
				// but we pass the DB timestamp. strtotime() on UTC timestamp + wp_date handles timezone.
				$timestamp                   = strtotime( $entry->created_at . ' UTC' );
				$entry->created_at_formatted = wp_date( $wp_format, $timestamp );
			} else {
				$entry->created_at_formatted = '-';
			}
		}

		/**
		 * SPIN HISTORY LOOKUP — How this works:
		 *
		 * Goal: For every entry row on the current page, find ALL previous spins made
		 * by the same user (matched by email OR phone), so we can show a spin count
		 * badge and a full spin history panel in the expanded row — with a SINGLE DB query.
		 *
		 * Step 1 — Collect unique identifiers from the current page entries.
		 *   $page_emails = all non-empty unique emails on this page (max ~20)
		 *   $page_phones = all non-empty unique phones on this page (max ~20)
		 *
		 * Step 2 — Build a composite key per entry for grouping.
		 *   Format: "email|phone"
		 *   Examples:
		 *     Email-only entry  →  "user@email.com|"
		 *     Phone-only entry  →  "|01793330005"        ← the | MUST stay, do NOT trim it!
		 *     Both              →  "user@email.com|01793330005"
		 *   IMPORTANT: We do NOT trim() the | because phone-only keys start with |.
		 *   If we trim, the phone lands in the email slot when we explode(), and matching breaks.
		 *
		 * Step 3 — One SQL query: WHERE email IN (...) OR phone IN (...)
		 *   This fetches all spins for all identifiers in one round-trip. No loops to DB.
		 *
		 * Step 4 — Map each fetched spin row back to matching entry keys.
		 *   A spin can match an entry by email, by phone, or both.
		 *   We deduplicate so the same spin row isn't added to the same key twice
		 *   (edge case: entry has both email + phone, and the spin matches both).
		 *
		 * Step 5 — Attach spin_count and spin_history to each entry.
		 *   spin_count    = total spins by this identity (including the current row)
		 *   spin_history  = all spins EXCEPT the current entry's own row
		 */
		$page_emails = array_values( array_filter( array_unique( array_column( $entries, 'email' ) ) ) );
		$page_phones = array_values( array_filter( array_unique( array_column( $entries, 'phone' ) ) ) );

		$history_map = []; // keyed by "email|phone" composite identity => [ spin rows ]

		// Build a helper: entry id => composite key, for attaching history later
		$entry_key_map = [];
		foreach ( $entries as $entry ) {
			$entry_key_map[ $entry->id ] = ( $entry->email ?? '' ) . '|' . ( $entry->phone ?? '' );
		}

		$has_emails = ! empty( $page_emails );
		$has_phones = ! empty( $page_phones );

		if ( $has_emails || $has_phones ) {
			// Build WHERE: (email IN (...)) OR (phone IN (...))
			$conditions   = [];
			$bind_values  = [];

			if ( $has_emails ) {
				$placeholders  = implode( ',', array_fill( 0, count( $page_emails ), '%s' ) );
				$conditions[]  = "email IN ($placeholders)";
				$bind_values   = array_merge( $bind_values, $page_emails );
			}
			if ( $has_phones ) {
				$placeholders  = implode( ',', array_fill( 0, count( $page_phones ), '%s' ) );
				$conditions[]  = "phone IN ($placeholders)";
				$bind_values   = array_merge( $bind_values, $page_phones );
			}

			$history_where = implode( ' OR ', $conditions );

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$all_spins = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, email, phone, campaign_id, campaign_title, others_data, created_at FROM {$wpdb->prefix}wdengage_entries WHERE $history_where ORDER BY created_at DESC",
					$bind_values
				)
			);

			foreach ( $all_spins as $spin ) {
				// Format date
				$spin->created_at_formatted = '-';
				if ( ! empty( $spin->created_at ) ) {
					$ts                         = strtotime( $spin->created_at . ' UTC' );
					$spin->created_at_formatted = wp_date( $wp_format, $ts );
				}

				// Map this spin row to all matching entry keys (an email-only entry and a phone-only entry may both match)
				$spin_email = $spin->email ?? '';
				$spin_phone = $spin->phone ?? '';

				foreach ( $entry_key_map as $entry_id => $entry_key ) {
					[ $e_email, $e_phone ] = explode( '|', $entry_key . '|' ); // safe split
					$matches_email = $spin_email && $e_email && $spin_email === $e_email;
					$matches_phone = $spin_phone && $e_phone && $spin_phone === $e_phone;
					if ( $matches_email || $matches_phone ) {
						if ( ! isset( $history_map[ $entry_key ] ) ) {
							$history_map[ $entry_key ] = [];
						}
						// Avoid duplicating the same spin row under the same key
						$already_added = false;
						foreach ( $history_map[ $entry_key ] as $existing ) {
							if ( (int) $existing->id === (int) $spin->id ) {
								$already_added = true;
								break;
							}
						}
						if ( ! $already_added ) {
							$history_map[ $entry_key ][] = $spin;
						}
					}
				}
			}
		}

		// Attach spin_count and spin_history to each entry (exclude current entry from its own history).
		foreach ( $entries as $entry ) {
			$key                  = $entry_key_map[ $entry->id ] ?? '';
			$all_for_identity     = $history_map[ $key ] ?? [];
			$entry->spin_count    = count( $all_for_identity );
			$entry->spin_history  = array_values(
				array_filter( $all_for_identity, fn( $s ) => (int) $s->id !== (int) $entry->id )
			);
		}

		$result = [
			'entries'      => $entries,
			'total'        => intval( $total ),
			'total_pages'  => intval( $total_pages ),
			'current_page' => $page,
			'per_page'     => $per_page,
		];

		// NOTE: Caching removed intentionally - causes stale data issues with paginated entries

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
		$result = $wpdb->delete( $table_name, [ 'id' => $id ], [ '%d' ] );

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

		$ids = isset( $_POST['ids'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['ids'] ) ) : [];
		// Sanitize each id
		if ( ! is_array( $ids ) ) {
			$ids = [ $ids ];
		}
		if ( empty( $ids ) ) {
			wp_send_json_error( 'No entries selected' );
		}

		// Sanitize IDs
		$ids = array_map( 'intval', $ids );
		$ids = array_filter($ids, function ( $id ) {
			return $id > 0;
		});

		if ( empty( $ids ) ) {
			wp_send_json_error( 'Invalid entry IDs' );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'wdengage_entries';

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$delete_sql   = 'DELETE FROM ' . $wpdb->prefix . 'wdengage_entries WHERE id IN (' . $placeholders . ')';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter
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

		// Use the same filtering logic as get_entries for consistency
		$filters = [
			'search'      => isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '',
			'campaign_id' => isset( $_POST['campaign_id'] ) ? sanitize_text_field( wp_unslash( $_POST['campaign_id'] ) ) : '',
			'win_status'  => isset( $_POST['win_status'] ) ? sanitize_text_field( wp_unslash( $_POST['win_status'] ) ) : '',
			'start_date'  => isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '',
			'end_date'    => isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : '',
			'lead_status' => isset( $_POST['lead_status'] ) ? sanitize_text_field( wp_unslash( $_POST['lead_status'] ) ) : '',
		];

		// Apply sanitization helper
		array_walk( $filters, [ $this, 'sanitize_filter_params' ] );

		$search      = $filters['search'];
		$campaign_id = $filters['campaign_id'];
		$win_status  = $filters['win_status'];
		$start_date  = $filters['start_date'];
		$end_date    = $filters['end_date'];
		$lead_status = $filters['lead_status'];

		// Build WHERE clause (Mirrors get_entries)
		$where_conditions = [];
		$where_values     = [];

		if ( ! empty( $search ) ) {
			$where_conditions[] = '(name LIKE %s OR email LIKE %s OR phone LIKE %s OR campaign_title LIKE %s OR others_data LIKE %s)';
			$search_term        = '%' . $wpdb->esc_like( $search ) . '%';
			$where_values[]     = $search_term;
			$where_values[]     = $search_term;
			$where_values[]     = $search_term;
			$where_values[]     = $search_term;
			$where_values[]     = $search_term;
		}

		if ( ! empty( $campaign_id ) ) {
			$where_conditions[] = 'campaign_id = %d';
			$where_values[]     = $campaign_id;
		}

		if ( ! empty( $win_status ) ) {
			if ( 'wins' === $win_status ) {
				$where_conditions[] = '(others_data LIKE %s OR (others_data LIKE %s AND others_data NOT LIKE %s))';
				$where_values[]     = '%"status":"wins"%';
				$where_values[]     = '%"coupon_code":"%';
				$where_values[]     = '%"coupon_code":""%';
			} else {
				$where_conditions[] = 'others_data LIKE %s AND others_data LIKE %s';
				$where_values[]     = '%"status":"lost"%';
				$where_values[]     = '%"coupon_code":""%';
			}
		}

		if ( ! empty( $start_date ) ) {
			// Convert Local Start Date to UTC for DB Query
			$utc_start = get_gmt_from_date( $start_date . ' 00:00:00' );
			$where_conditions[] = 'created_at >= %s';
			$where_values[]     = $utc_start;
		}

		if ( ! empty( $end_date ) ) {
			// Convert Local End Date to UTC for DB Query
			$utc_end = get_gmt_from_date( $end_date . ' 23:59:59' );
			$where_conditions[] = 'created_at <= %s';
			$where_values[]     = $utc_end;
		}

		if ( ! empty( $lead_status ) && 'all' !== $lead_status ) {
			if ( 'trash' === $lead_status ) {
				$where_conditions[] = 'status LIKE %s';
				$where_values[]     = '%"stage":"trash"%';
			} else {
				if ( 'new' === $lead_status ) {
					$where_conditions[] = '(status LIKE %s OR status IS NULL OR status = "" OR status NOT LIKE %s)';
					$where_values[]     = '%"stage":"new"%';
					$where_values[]     = '%"stage":%';
				} else {
					$where_conditions[] = 'status LIKE %s';
					$where_values[]     = '%"stage":"' . $wpdb->esc_like( $lead_status ) . '"%';
				}
				$where_conditions[] = '(status NOT LIKE %s OR status IS NULL)';
				$where_values[]     = '%"stage":"trash"%';
			}
		} else {
			$where_conditions[] = '(status NOT LIKE %s OR status IS NULL)';
			$where_values[]     = '%"stage":"trash"%';
		}

		$where_clause = '';
		if ( ! empty( $where_conditions ) ) {
			$where_clause = 'WHERE ' . implode( ' AND ', $where_conditions );
		}

		if ( ! empty( $where_values ) ) {
			$export_sql = 'SELECT * FROM ' . $wpdb->prefix . 'wdengage_entries ' . $where_clause . ' ORDER BY created_at DESC';
			$entries    = $wpdb->get_results( $wpdb->prepare( $export_sql, $where_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		} else {
			$entries = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wdengage_entries WHERE status IS NULL OR status NOT LIKE '%\"stage\":\"trash\"%' ORDER BY created_at DESC" );
		}

		if ( empty( $entries ) ) {
			wp_send_json_error( 'No entries found for export' );
		}

		// Prepare CSV data
		$csv_data   = [];
		$csv_data[] = [ 'ID', 'Name', 'Email', 'Phone', 'Campaign ID', 'Campaign Title', 'Result', 'Lead Stage', 'Coupon Title', 'Coupon Code', 'Created At' ];

		$wp_format   = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
		$wp_timezone = wp_timezone();

		foreach ( $entries as $entry ) {
			$others       = json_decode( $entry->others_data, true );
			$is_win       = ( isset( $others['status'] ) && $others['status'] === 'wins' ) || ! empty( $others['coupon_code'] );
			$result       = $is_win ? 'Win' : 'Loss';
			$coupon_title = $others['coupon_title'] ?? '-';
			$coupon_code  = $others['coupon_code'] ?? '-';

			// Get lead stage from status JSON
			$status_data = json_decode( $entry->status, true );
			$lead_stage  = isset( $status_data['stage'] ) ? ucfirst( $status_data['stage'] ) : 'New';

			// Format created_at for CSV according to WP timezone settings
			$formatted_date = $entry->created_at;
			if ( ! empty( $entry->created_at ) ) {
				try {
					$date = new \DateTime( $entry->created_at, new \DateTimeZone( 'UTC' ) );
					$date->setTimezone( $wp_timezone );
					$formatted_date = $date->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
				} catch ( \Exception $e ) {
					$formatted_date = $entry->created_at;
				}
			}

			$csv_data[] = [
				$entry->id,
				$entry->name,
				$entry->email,
				$entry->phone,
				$entry->campaign_id,
				$entry->campaign_title,
				$result,
				$lead_stage,
				$coupon_title,
				$coupon_code,
				$formatted_date,
			];
		}

		// Build CSV string
		$csv_string = '';
		foreach ( $csv_data as $row ) {
			$escaped_row = [];
			foreach ( $row as $field ) {
				$field = str_replace( '"', '""', $field );
				if ( strpos( $field, ',' ) !== false || strpos( $field, '"' ) !== false || strpos( $field, "\n" ) !== false ) {
					$field = '"' . $field . '"';
				}
				$escaped_row[] = $field;
			}
			$csv_string .= implode( ',', $escaped_row ) . "\n";
		}

		wp_send_json_success( $csv_string );
	}

	/**
	 * Generate cache key for entries
	 */
	private function get_cache_key( $params = [] ) {
		$key_parts = [
			'ultimate_spin_wheel_entries',
			isset( $params['page'] ) ? $params['page'] : 1,
			isset( $params['per_page'] ) ? $params['per_page'] : 20,
			isset( $params['search'] ) ? $params['search'] : '',
			isset( $params['campaign_id'] ) ? $params['campaign_id'] : '',
			isset( $params['win_status'] ) ? $params['win_status'] : '',
			isset( $params['start_date'] ) ? $params['start_date'] : '',
			isset( $params['end_date'] ) ? $params['end_date'] : '',
			isset( $params['lead_status'] ) ? $params['lead_status'] : '',
		];
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
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'ultimate-spin-wheel' ) ] );
		}
	}

	/**
	 * Get list of campaigns that have entries
	 */
	public function get_campaigns() {
		// Only allow if Pro is active
		if ( ! apply_filters( 'ultimate_spin_wheel_pro_init', false ) ) {
			wp_send_json_success( [] );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimate_spin_wheel' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'ultimate-spin-wheel' ) ] );
		}

		global $wpdb;
		$results = $wpdb->get_results( "SELECT campaign_id, MIN(campaign_title) as campaign_title FROM {$wpdb->prefix}wdengage_entries WHERE campaign_id IS NOT NULL AND campaign_id != 0 GROUP BY campaign_id ORDER BY campaign_title ASC" );

		wp_send_json_success( $results );
	}

	/**
	 * Update entry status
	 * Supports both single and bulk updates
	 */
	public function update_entry_status() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimate_spin_wheel' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'ultimate-spin-wheel' ) ] );
		}

		// Handle both 'ids' and 'ids[]' formats from frontend
		$ids = [];
		if ( isset( $_POST['ids'] ) ) {
			$raw_ids = wp_unslash( $_POST['ids'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			if ( is_array( $raw_ids ) ) {
				$ids = array_map( 'sanitize_text_field', $raw_ids );
			} else {
				$ids = [ sanitize_text_field( $raw_ids ) ];
			}
		}
		// Flatten if it's a nested array (from ids[] format)
		if ( isset( $ids[0] ) && is_array( $ids[0] ) ) {
			$ids = $ids[0];
		}

		$new_stage = isset( $_POST['stage'] ) ? sanitize_text_field( wp_unslash( $_POST['stage'] ) ) : '';

		// Sanitize IDs to integers and filter out invalid ones
		$ids = array_map( 'intval', $ids );
		$ids = array_filter($ids, function ( $id ) {
			return $id > 0;
		});

		if ( empty( $ids ) || empty( $new_stage ) ) {
			wp_send_json_error( [ 'message' => 'Missing parameters (ids: ' . count( $ids ) . ', stage: ' . $new_stage . ')' ] );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'wdengage_entries';

		$success_count = 0;
		$skipped_ids   = [];
		$debug_info    = [];

		foreach ( $ids as $id ) {
			$id = intval( $id );
			if ( ! $id ) {
				continue;
			}

			// Debug: Mark that we're processing this ID
			$debug_info[] = "Processing ID: $id";

			// Check if entry exists first
			$entry_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE id = %d", $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			if ( null === $entry_exists ) {
				// Entry doesn't exist in database, skip it
				$skipped_ids[] = $id;
				$debug_info[]  = "ID $id not found in table $table_name";
				continue;
			}

			$debug_info[] = "ID $id exists in DB";

			// Get current status
			$current_status_raw = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM $table_name WHERE id = %d", $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$status_data        = json_decode( (string) $current_status_raw, true );

			if ( ! is_array( $status_data ) ) {
				// Legacy or empty status
				$status_data = [
					'stage'   => 'new',
					'history' => [],
				];
				if ( ! empty( $current_status_raw ) && strpos( $current_status_raw, '{' ) === false ) {
					$status_data['stage'] = $current_status_raw; // Preserve legacy string if it exists
				}
			}

			$debug_info[] = 'Current stage: ' . ( $status_data['stage'] ?? 'unknown' ) . ", New stage: $new_stage";

			// Add to history
			$status_data['history'][] = [
				'from' => $status_data['stage'],
				'to'   => $new_stage,
				'at'   => current_time( 'mysql' ),
			];

			$status_data['stage']      = $new_stage;
			$status_data['updated_at'] = current_time( 'mysql' );

			$updated = $wpdb->update(
				$table_name,
				[ 'status' => json_encode( $status_data ) ],
				[ 'id' => $id ],
				[ '%s' ],
				[ '%d' ]
			);

			$debug_info[] = "Update result for ID $id: " . json_encode( $updated );

			if ( false === $updated ) {
				// Database error occurred
				$debug_info[] = "ID $id: DB error - " . $wpdb->last_error;
				$debug_info[] = 'Last Query: ' . $wpdb->last_query;
			} elseif ( 0 === $updated ) {
				// No rows affected (data might be the same)
				$debug_info[] = "ID $id: 0 rows affected (no change or row not found)";
				++$success_count; // Still count as success since no error
			} else {
				$debug_info[] = "ID $id: Successfully updated ($updated rows)";
				++$success_count;
			}
		}

		$this->clear_entries_cache();

		wp_send_json_success([
			/* translators: 1: Number of leads updated, 2: New stage name */
			'message'       => sprintf( __( '%1$d leads updated to %2$s', 'ultimate-spin-wheel' ), $success_count, $new_stage ),
			'updated_count' => $success_count,
			'processed_ids' => $ids,
			'skipped_ids'   => $skipped_ids,
			'debug_info'    => $debug_info,
			'table_name'    => $table_name,
		]);
	}

	/**
	 * Update entry notes
	 * Saves notes in the others_data JSON column (Moved to Pro)
	 */
	public function update_entry_notes() {
		wp_send_json_error( 'This feature is only available in the Pro version.' );
	}


	/**
	 * Block an IP and Device ID from a specific entry
	 */
	public function block_identity() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimate_spin_wheel' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'ultimate-spin-wheel' ) ] );
		}

		$entry_id = isset( $_POST['entry_id'] ) ? intval( $_POST['entry_id'] ) : 0;
		if ( ! $entry_id ) {
			wp_send_json_error( 'Invalid entry ID' );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'wdengage_entries';
		$entry      = $wpdb->get_row( $wpdb->prepare( "SELECT user_data, status FROM {$table_name} WHERE id = %d", $entry_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		if ( ! $entry ) {
			wp_send_json_error( 'Entry not found' );
		}

		$user_data = json_decode( $entry->user_data, true ) ?: [];
		$ip        = $user_data['ip_address'] ?? '';
		$device_id = $user_data['device_id'] ?? '';

		if ( empty( $ip ) && empty( $device_id ) ) {
			wp_send_json_error( 'No identifier found to block' );
		}

		$settings = get_option( 'uspw_global_settings' );
		$settings = $settings ? json_decode( $settings, true ) : [];

		if ( ! isset( $settings['security'] ) ) {
			$settings['security'] = [
				'blocked_ips'     => [],
				'blocked_devices' => [],
			];
		}

		if ( ! empty( $ip ) && ! in_array( $ip, $settings['security']['blocked_ips'], true ) ) {
			$settings['security']['blocked_ips'][] = $ip;
		}

		if ( ! empty( $device_id ) && ! in_array( $device_id, $settings['security']['blocked_devices'], true ) ) {
			$settings['security']['blocked_devices'][] = $device_id;
		}

		update_option( 'uspw_global_settings', wp_json_encode( $settings ) );

		// Update entry status to mark as blocked
		$status            = json_decode( $entry->status, true ) ?: [];
		$status['blocked'] = true;
		$wpdb->update(
			$table_name,
			[ 'status' => wp_json_encode( $status ) ],
			[ 'id' => $entry_id ]
		);

		wp_send_json_success( [ 'message' => __( 'Identity blocked successfully', 'ultimate-spin-wheel' ) ] );
	}

	/**
	 * Unblock an identity (IP or Device ID)
	 */
	public function unblock_identity() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimate_spin_wheel' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'ultimate-spin-wheel' ) ] );
		}

		$type  = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : ''; // 'ip' or 'device'
		$value = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : '';

		if ( empty( $type ) || empty( $value ) ) {
			wp_send_json_error( 'Invalid data provided' );
		}

		$settings = get_option( 'uspw_global_settings' );
		$settings = $settings ? json_decode( $settings, true ) : [];

		if ( isset( $settings['security'] ) ) {
			if ( 'ip' === $type && isset( $settings['security']['blocked_ips'] ) ) {
				$settings['security']['blocked_ips'] = array_values( array_diff( $settings['security']['blocked_ips'], [ $value ] ) );
			} elseif ( 'device' === $type && isset( $settings['security']['blocked_devices'] ) ) {
				$settings['security']['blocked_devices'] = array_values( array_diff( $settings['security']['blocked_devices'], [ $value ] ) );
			}
			update_option( 'uspw_global_settings', wp_json_encode( $settings ) );
		}

		wp_send_json_success( [ 'message' => __( 'Identity unblocked successfully', 'ultimate-spin-wheel' ) ] );
	}
}
