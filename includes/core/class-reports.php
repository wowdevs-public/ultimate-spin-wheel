<?php

namespace USPIN_WHEEL\Includes\Core;

defined( 'ABSPATH' ) || exit;

class Reports {



	private static $instance = null;

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'wp_ajax_ultimate_spin_wheel_reports', [ $this, 'reports' ] );
		add_action( 'wp_ajax_nopriv_ultimate_spin_wheel_reports', [ $this, 'reports' ] );
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
	 * Main Reports Function
	 */
	public function reports() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimate_spin_wheel' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'ultimate-spin-wheel' ) ] );
		}

		$campaign_id = isset( $_POST['campaign_id'] ) ? intval( wp_unslash( $_POST['campaign_id'] ) ) : 0;
		$start_date  = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
		$end_date    = isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : '';

		$filters = [
			'campaign_id' => $campaign_id,
			'start_date'  => $start_date,
			'end_date'    => $end_date,
			'exclude_trash' => true, // Default to excluding trash
		];

		$reports = [
			'total_engagements'         => $this->get_total_engagements( $filters ),
			'total_winners'             => $this->get_total_winners( $filters ),
			'total_losers'              => $this->get_total_losers( $filters ),
			'total_leads'               => $this->get_total_leads( $filters ),
			'total_emails'              => $this->get_total_emails( $filters ),
			'total_phones'              => $this->get_total_phones( $filters ),
			'today_engagements'         => $this->get_today_engagements( $filters ),
			'today_winners'             => $this->get_today_winners( $filters ),
			'today_losers'              => $this->get_today_losers( $filters ),
			'today_leads'               => $this->get_today_leads( $filters ),
			'yesterday_engagements'     => $this->get_yesterday_engagements( $filters ),
			'yesterday_winners'         => $this->get_yesterday_winners( $filters ),
			'conversion_rate'           => $this->get_conversion_rate( $filters ),
			'win_rate'                  => $this->get_win_rate( $filters ),
			'last_30_days_engagements'  => $this->last_30_days_engagements( $filters ),
			'last_30_days_winners'      => $this->last_30_days_winners( $filters ),
			'last_30_days_leads'        => $this->last_30_days_leads( $filters ),
			'engagement_by_campaigns'   => $this->get_engagement_by_campaigns( $filters ),
			'winners_by_campaigns'      => $this->get_winners_by_campaigns( $filters ),
			'monthly_trends'            => $this->get_monthly_trends( $filters ),
			'hourly_engagement_pattern' => $this->get_hourly_engagement_pattern( $filters ),
			'days_engagement_pattern'   => $this->get_days_engagement_pattern( $filters ),
			'weekly_engagement_pattern' => $this->get_weekly_engagement_pattern( $filters ),
			'prize_distribution'        => $this->get_prize_distribution( $filters ),
			'top_coupons'               => $this->get_top_coupons( $filters ),
			'recent_activities'         => $this->get_recent_activities( $filters ),
			// New Creative Insights
			'insights'                  => $this->get_insights( $filters ),
		];

		wp_send_json_success( $reports );
	}

	/**
	 * Get Total Engagements
	 */
	private function get_total_engagements( $filters = [] ) {
		global $wpdb;
		$where = $this->get_where_clause( $filters );
		$query = "SELECT COUNT(*) FROM {$wpdb->prefix}wdengage_entries {$where['clause']}";
		return (int) $wpdb->get_var( $wpdb->prepare( $query, $where['values'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	/**
	 * Get Total Winners
	 */
	private function get_total_winners( $filters = [] ) {
		global $wpdb;
		$filters['is_winner'] = true;
		$where                = $this->get_where_clause( $filters );
		$query                = "SELECT COUNT(*) FROM {$wpdb->prefix}wdengage_entries {$where['clause']}";
		return (int) $wpdb->get_var( $wpdb->prepare( $query, $where['values'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	/**
	 * Get Total Losers
	 */
	private function get_total_losers( $filters = [] ) {
		global $wpdb;
		$filters['is_winner'] = false;
		$where                = $this->get_where_clause( $filters );
		$query                = "SELECT COUNT(*) FROM {$wpdb->prefix}wdengage_entries {$where['clause']}";
		return (int) $wpdb->get_var( $wpdb->prepare( $query, $where['values'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	/**
	 * Get Total Leads (unique identities by email or phone)
	 */
	private function get_total_leads( $filters = [] ) {
		global $wpdb;
		$where  = $this->get_where_clause( $filters );
		$prefix = empty( $where['clause'] ) ? 'WHERE' : $where['clause'] . ' AND';
		$query  = "SELECT COUNT(DISTINCT CASE WHEN (email != '' AND email IS NOT NULL) THEN email ELSE (CASE WHEN (phone != '' AND phone IS NOT NULL) THEN phone ELSE NULL END) END) FROM {$wpdb->prefix}wdengage_entries {$prefix} ((email != '' AND email IS NOT NULL) OR (phone != '' AND phone IS NOT NULL))";
		return (int) $wpdb->get_var( $wpdb->prepare( $query, $where['values'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	/**
	 * Get Total Emails
	 */
	private function get_total_emails( $filters = [] ) {
		global $wpdb;
		$where  = $this->get_where_clause( $filters );
		$prefix = empty( $where['clause'] ) ? 'WHERE' : $where['clause'] . ' AND';
		$query  = "SELECT COUNT(DISTINCT email) FROM {$wpdb->prefix}wdengage_entries {$prefix} email != '' AND email IS NOT NULL";
		return (int) $wpdb->get_var( $wpdb->prepare( $query, $where['values'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	/**
	 * Get Total Phones
	 */
	private function get_total_phones( $filters = [] ) {
		global $wpdb;
		$where  = $this->get_where_clause( $filters );
		$prefix = empty( $where['clause'] ) ? 'WHERE' : $where['clause'] . ' AND';
		$query  = "SELECT COUNT(DISTINCT phone) FROM {$wpdb->prefix}wdengage_entries {$prefix} phone != '' AND phone IS NOT NULL";
		return (int) $wpdb->get_var( $wpdb->prepare( $query, $where['values'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	/**
	 * Get Today's Engagements
	 */
	private function get_today_engagements( $filters = [] ) {
		global $wpdb;
		$filters['start_date'] = current_time( 'Y-m-d' );
		$filters['end_date']   = current_time( 'Y-m-d' );
		$where                 = $this->get_where_clause( $filters );
		$query                 = "SELECT COUNT(*) FROM {$wpdb->prefix}wdengage_entries {$where['clause']}";
		return (int) $wpdb->get_var( $wpdb->prepare( $query, $where['values'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	/**
	 * Get Today's Winners
	 */
	private function get_today_winners( $filters = [] ) {
		global $wpdb;
		$filters['start_date'] = current_time( 'Y-m-d' );
		$filters['end_date']   = current_time( 'Y-m-d' );
		$filters['is_winner']  = true;
		$where                 = $this->get_where_clause( $filters );
		$query                 = "SELECT COUNT(*) FROM {$wpdb->prefix}wdengage_entries {$where['clause']}";
		return (int) $wpdb->get_var( $wpdb->prepare( $query, $where['values'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	/**
	 * Get Today's Losers
	 */
	private function get_today_losers( $filters = [] ) {
		global $wpdb;
		$filters['start_date'] = current_time( 'Y-m-d' );
		$filters['end_date']   = current_time( 'Y-m-d' );
		$filters['is_winner']  = false;
		$where                 = $this->get_where_clause( $filters );
		$query                 = "SELECT COUNT(*) FROM {$wpdb->prefix}wdengage_entries {$where['clause']}";
		return (int) $wpdb->get_var( $wpdb->prepare( $query, $where['values'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	/**
	 * Get Today's Leads
	 */
	private function get_today_leads( $filters = [] ) {
		global $wpdb;
		$filters['start_date'] = current_time( 'Y-m-d' );
		$filters['end_date']   = current_time( 'Y-m-d' );
		$where                 = $this->get_where_clause( $filters );
		$prefix                = empty( $where['clause'] ) ? 'WHERE' : $where['clause'] . ' AND';
		$query                 = "SELECT COUNT(DISTINCT CASE WHEN (email != '' AND email IS NOT NULL) THEN email ELSE (CASE WHEN (phone != '' AND phone IS NOT NULL) THEN phone ELSE NULL END) END) FROM {$wpdb->prefix}wdengage_entries {$prefix} ((email != '' AND email IS NOT NULL) OR (phone != '' AND phone IS NOT NULL))";
		return (int) $wpdb->get_var( $wpdb->prepare( $query, $where['values'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	/**
	 * Get Yesterday's Engagements
	 */
	private function get_yesterday_engagements( $filters = [] ) {
		global $wpdb;
		$yesterday             = current_datetime()->modify( '-1 day' )->format( 'Y-m-d' );
		$filters['start_date'] = $yesterday;
		$filters['end_date']   = $yesterday;
		$where                 = $this->get_where_clause( $filters );
		$query                 = "SELECT COUNT(*) FROM {$wpdb->prefix}wdengage_entries {$where['clause']}";
		return (int) $wpdb->get_var( $wpdb->prepare( $query, $where['values'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}

	/**
	 * Get Yesterday's Winners
	 */
	private function get_yesterday_winners( $filters = [] ) {
		global $wpdb;
		$yesterday             = current_datetime()->modify( '-1 day' )->format( 'Y-m-d' );
		$filters['start_date'] = $yesterday;
		$filters['end_date']   = $yesterday;
		$filters['is_winner']  = true;
		$where                 = $this->get_where_clause( $filters );
		$query                 = "SELECT COUNT(*) FROM {$wpdb->prefix}wdengage_entries {$where['clause']}";
		return (int) $wpdb->get_var( $wpdb->prepare( $query, $where['values'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}
	private function get_conversion_rate( $filters = [] ) {
		$total   = $this->get_total_engagements( $filters );
		$winners = $this->get_total_winners( $filters );
		return $total > 0 ? round( ( $winners / $total ) * 100, 2 ) : 0;
	}

	/**
	 * Get Win Rate (Winners/Total Engagements)
	 */
	private function get_win_rate( $filters = [] ) {
		return $this->get_conversion_rate( $filters );
	}

	/**
	 * Last 30 Days Engagements Data
	 */
	public function last_30_days_engagements( $filters = [] ) {
		global $wpdb;

		$wp_timezone    = wp_timezone();
		$offset_seconds = $wp_timezone->getOffset( new \DateTime( 'now', $wp_timezone ) );
		$tz_offset      = $offset_seconds / 3600;

		$where     = $this->get_where_clause( $filters );
		$prefix    = empty( $where['clause'] ) ? 'WHERE' : $where['clause'] . ' AND';

		$query = "SELECT COUNT(*) as total, DATE(DATE_ADD(created_at, INTERVAL %f HOUR)) as date
			 FROM {$wpdb->prefix}wdengage_entries
			 {$prefix} DATE_ADD(created_at, INTERVAL %f HOUR) >= %s - INTERVAL %d DAY
			 GROUP BY date
			 ORDER BY date ASC";

		$query_values = array_merge( [ $tz_offset ], $where['values'], [ $tz_offset, current_time( 'Y-m-d' ), 29 ] );
		$results      = $wpdb->get_results( $wpdb->prepare( $query, $query_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		return $this->format_chart_data( $results, 'Last 30 Days Engagements', 'rgba(59, 130, 246, 0.8)' );
	}

	/**
	 * Last 30 Days Winners Data
	 */
	public function last_30_days_winners( $filters = [] ) {
		global $wpdb;
		$filters['is_winner'] = true;

		$wp_timezone    = wp_timezone();
		$offset_seconds = $wp_timezone->getOffset( new \DateTime( 'now', $wp_timezone ) );
		$tz_offset      = $offset_seconds / 3600;

		$where     = $this->get_where_clause( $filters );
		$prefix    = empty( $where['clause'] ) ? 'WHERE' : $where['clause'] . ' AND';

		$query = "SELECT COUNT(*) as total, DATE(DATE_ADD(created_at, INTERVAL %f HOUR)) as date
			 FROM {$wpdb->prefix}wdengage_entries
			 {$prefix} DATE_ADD(created_at, INTERVAL %f HOUR) >= %s - INTERVAL %d DAY
			 GROUP BY date
			 ORDER BY date ASC";

		$query_values = array_merge( [ $tz_offset ], $where['values'], [ $tz_offset, current_time( 'Y-m-d' ), 29 ] );
		$results      = $wpdb->get_results( $wpdb->prepare( $query, $query_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		return $this->format_chart_data( $results, 'Last 30 Days Winners', 'rgba(34, 197, 94, 0.8)' );
	}

	/**
	 * Last 30 Days Leads Data
	 */
	public function last_30_days_leads( $filters = [] ) {
		global $wpdb;

		$wp_timezone    = wp_timezone();
		$offset_seconds = $wp_timezone->getOffset( new \DateTime( 'now', $wp_timezone ) );
		$tz_offset      = $offset_seconds / 3600;

		$where     = $this->get_where_clause( $filters );
		$prefix    = empty( $where['clause'] ) ? 'WHERE' : $where['clause'] . ' AND';

		$query = "SELECT COUNT(DISTINCT CASE WHEN (email != '' AND email IS NOT NULL) THEN email ELSE (CASE WHEN (phone != '' AND phone IS NOT NULL) THEN phone ELSE NULL END) END) as total, DATE(DATE_ADD(created_at, INTERVAL %f HOUR)) as date
			 FROM {$wpdb->prefix}wdengage_entries
			 {$prefix} ((email != '' AND email IS NOT NULL) OR (phone != '' AND phone IS NOT NULL)) AND DATE_ADD(created_at, INTERVAL %f HOUR) >= %s - INTERVAL %d DAY
			 GROUP BY date
			 ORDER BY date ASC";

		$query_values = array_merge( [ $tz_offset ], $where['values'], [ $tz_offset, current_time( 'Y-m-d' ), 29 ] );
		$results      = $wpdb->get_results( $wpdb->prepare( $query, $query_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		return $this->format_chart_data( $results, 'Last 30 Days Leads', 'rgba(168, 85, 247, 0.8)' );
	}

	/**
	 * Get Engagement by Campaigns
	 */
	private function get_engagement_by_campaigns( $filters = [] ) {
		global $wpdb;

		$where = $this->get_where_clause( $filters );
		$query = "SELECT campaign_title, COUNT(*) as total
			 FROM {$wpdb->prefix}wdengage_entries
			 {$where['clause']}
			 GROUP BY campaign_title
			 ORDER BY total DESC
			 LIMIT %d";

		$query_values = array_merge( $where['values'], [ 10 ] );
		$results      = $wpdb->get_results( $wpdb->prepare( $query, $query_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$labels = [];
		$data   = [];
		$colors = [];

		foreach ( $results as $result ) {
			$labels[] = $result->campaign_title ? $result->campaign_title : 'Unknown Campaign';
			$data[]   = (int) $result->total;
			$colors[] = $this->get_random_color();
		}

		return [
			'labels' => $labels,
			'datasets' => [
				[
					'label'           => 'Engagements by Campaign',
					'data'            => $data,
					'backgroundColor' => $colors,
					'borderWidth'     => 1,
				],
			],
		];
	}

	/**
	 * Get Winners by Campaigns
	 */
	private function get_winners_by_campaigns( $filters = [] ) {
		global $wpdb;
		$filters['is_winner'] = true;

		$where = $this->get_where_clause( $filters );
		$query = "SELECT campaign_title, COUNT(*) as total
			 FROM {$wpdb->prefix}wdengage_entries
			 {$where['clause']}
			 GROUP BY campaign_title
			 ORDER BY total DESC
			 LIMIT %d";

		$query_values = array_merge( $where['values'], [ 10 ] );
		$results      = $wpdb->get_results( $wpdb->prepare( $query, $query_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$labels = [];
		$data   = [];
		$colors = [];

		foreach ( $results as $result ) {
			$labels[] = $result->campaign_title ? $result->campaign_title : 'Unknown Campaign';
			$data[]   = (int) $result->total;
			$colors[] = $this->get_random_color();
		}

		return [
			'labels' => $labels,
			'datasets' => [
				[
					'label'           => 'Winners by Campaign',
					'data'            => $data,
					'backgroundColor' => $colors,
					'borderWidth'     => 1,
				],
			],
		];
	}

	/**
	 * Get Monthly Trends (Last 12 Months)
	 */
	private function get_monthly_trends( $filters = [] ) {
		global $wpdb;

		$where  = $this->get_where_clause( $filters );
		$prefix = empty( $where['clause'] ) ? 'WHERE' : $where['clause'] . ' AND';

		$wp_timezone    = wp_timezone();
		$offset_seconds = $wp_timezone->getOffset( new \DateTime( 'now', $wp_timezone ) );
		$tz_offset      = $offset_seconds / 3600;

		$start_date = current_datetime()->modify( '-12 months' )->setTime( 0, 0, 0 )->format( 'Y-m-d H:i:s' );
		$utc_start  = get_gmt_from_date( $start_date );

		// Apply offset to created_at BEFORE formatting the month
		$sql = "SELECT 
				COUNT(*) as total_engagements,
				COUNT(CASE WHEN (others_data LIKE '%%\"status\":\"wins\"%%' OR others_data LIKE '%%\"status\":\"won\"%%' OR (others_data LIKE '%%\"coupon_code\":\"%%' AND others_data NOT LIKE '%%\"coupon_code\":\"\"%%')) THEN 1 END) as total_winners,
				COUNT(DISTINCT CASE WHEN (email != '' AND email IS NOT NULL) THEN email ELSE (CASE WHEN (phone != '' AND phone IS NOT NULL) THEN phone ELSE NULL END) END) as total_leads,
				DATE_FORMAT(DATE_ADD(created_at, INTERVAL %f HOUR), '%Y-%m') as month
			 FROM {$wpdb->prefix}wdengage_entries
			 {$where['clause']} " . ( empty( $where['clause'] ) ? 'WHERE' : 'AND' ) . " created_at >= %s
			 GROUP BY month
			 ORDER BY month ASC";

		$query_values = array_merge( [ $tz_offset ], $where['values'], [ $utc_start ] );
		$results      = $wpdb->get_results( $wpdb->prepare( $sql, $query_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$labels      = [];
		$engagements = [];
		$winners     = [];
		$leads       = [];

		foreach ( $results as $result ) {
			$labels[]      = gmdate( 'M Y', strtotime( $result->month . '-01' ) );
			$engagements[] = (int) $result->total_engagements;
			$winners[]     = (int) $result->total_winners;
			$leads[]       = (int) $result->total_leads;
		}

		return [
			'labels' => $labels,
			'datasets' => [
				[
					'label'           => 'Total Engagements',
					'data'            => $engagements,
					'borderColor'     => 'rgb(59, 130, 246)',
					'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
					'tension'         => 0.3,
				],
				[
					'label'           => 'Winners',
					'data'            => $winners,
					'borderColor'     => 'rgb(34, 197, 94)',
					'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
					'tension'         => 0.3,
				],
				[
					'label'           => 'Leads',
					'data'            => $leads,
					'borderColor'     => 'rgb(168, 85, 247)',
					'backgroundColor' => 'rgba(168, 85, 247, 0.1)',
					'tension'         => 0.3,
				],
			],
		];
	}

	/**
	 * Get Hourly Engagement Pattern
	 */
	private function get_hourly_engagement_pattern( $filters = [] ) {
		global $wpdb;

		$where  = $this->get_where_clause( $filters );
		$prefix = empty( $where['clause'] ) ? 'WHERE' : $where['clause'] . ' AND';

		// Get WordPress timezone offset in hours
		$wp_timezone    = wp_timezone();
		$now            = new \DateTime( 'now', $wp_timezone );
		$offset_seconds = $wp_timezone->getOffset( $now );
		$offset_hours   = $offset_seconds / 3600;

		// Normalize UTC cutoff time
		$start_date = current_datetime()->modify( '-7 days' )->setTime( 0, 0, 0 )->format( 'Y-m-d H:i:s' );
		$utc_start  = get_gmt_from_date( $start_date );

		// Convert created_at from UTC to WordPress timezone before extracting hour
		$query = "SELECT HOUR(DATE_ADD(created_at, INTERVAL %f HOUR)) as hour, COUNT(*) as total
			 FROM {$wpdb->prefix}wdengage_entries
			 {$prefix} created_at >= %s
			 GROUP BY HOUR(DATE_ADD(created_at, INTERVAL %f HOUR))
			 ORDER BY hour ASC";

		$query_values = array_merge( [ $offset_hours ], $where['values'], [ $utc_start, $offset_hours ] );
		$results      = $wpdb->get_results( $wpdb->prepare( $query, $query_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$labels = [];
		$data   = [];

		// Initialize all hours with 0, using 12-hour format with AM/PM
		for ( $i = 0; $i < 24; $i++ ) {
			if ( $i === 0 ) {
				$labels[] = '12 AM';
			} elseif ( $i < 12 ) {
				$labels[] = $i . ' AM';
			} elseif ( $i === 12 ) {
				$labels[] = '12 PM';
			} else {
				$labels[] = ( $i - 12 ) . ' PM';
			}
			$data[] = 0;
		}

		// Fill in actual data
		foreach ( $results as $result ) {
			$hour = (int) $result->hour;
			// Handle hours that wrap around due to timezone offset
			if ( $hour >= 0 && $hour < 24 ) {
				$data[ $hour ] = (int) $result->total;
			}
		}

		return [
			'labels' => $labels,
			'datasets' => [
				[
					'label'           => __( 'Hourly Engagement', 'ultimate-spin-wheel' ),
					'data'            => $data,
					'backgroundColor' => 'rgba(249, 115, 22, 0.6)',
					'borderColor'     => 'rgb(249, 115, 22)',
					'borderWidth'     => 2,
				],
			],
		];
	}

	/**
	 * Get Days Engagement Pattern
	 */
	private function get_days_engagement_pattern( $filters = [] ) {
		global $wpdb;
		$where  = $this->get_where_clause( $filters );
		$prefix = empty( $where['clause'] ) ? 'WHERE' : $where['clause'] . ' AND';

		// Get WordPress timezone offset
		$wp_timezone    = wp_timezone();
		$offset_seconds = $wp_timezone->getOffset( new \DateTime( 'now', $wp_timezone ) );
		$tz_offset      = $offset_seconds / 3600;

		$start_date = current_datetime()->modify( '-30 days' )->setTime( 0, 0, 0 )->format( 'Y-m-d H:i:s' );
		$utc_start  = get_gmt_from_date( $start_date );

		$query = "SELECT DAYOFWEEK(DATE_ADD(created_at, INTERVAL %f HOUR)) as day, COUNT(*) as total
			 FROM {$wpdb->prefix}wdengage_entries
			 {$prefix} created_at >= %s
			 GROUP BY DAYOFWEEK(DATE_ADD(created_at, INTERVAL %f HOUR))
			 ORDER BY day ASC";

		$query_values = array_merge( [ $tz_offset ], $where['values'], [ $utc_start, $tz_offset ] );
		$results      = $wpdb->get_results( $wpdb->prepare( $query, $query_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$labels = [ 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ];
		$data   = array_fill( 0, 7, 0 );

		foreach ( $results as $result ) {
			$day_index = (int) $result->day - 1;
			if ( $day_index >= 0 && $day_index < 7 ) {
				$data[ $day_index ] = (int) $result->total;
			}
		}

		return [
			'labels' => $labels,
			'datasets' => [
				[
					'label'           => __( 'Engagements', 'ultimate-spin-wheel' ),
					'data'            => $data,
					'backgroundColor' => 'rgba(236, 72, 153, 0.7)',
					'borderColor'     => 'rgb(236, 72, 153)',
					'borderWidth'     => 2,
					'borderRadius'    => 6,
				],
			],
		];
	}

	/**
	 * Get Weekly Engagement Pattern (Last 8 Weeks)
	 */
	private function get_weekly_engagement_pattern( $filters = [] ) {
		global $wpdb;

		$where  = $this->get_where_clause( $filters );
		$prefix = empty( $where['clause'] ) ? 'WHERE' : $where['clause'] . ' AND';

		// Get WordPress timezone
		$wp_timezone    = wp_timezone();
		$offset_seconds = $wp_timezone->getOffset( new \DateTime( 'now', $wp_timezone ) );
		$tz_offset      = $offset_seconds / 3600;

		$start_date = current_datetime()->modify( '-8 weeks' )->setTime( 0, 0, 0 )->format( 'Y-m-d H:i:s' );
		$utc_start  = get_gmt_from_date( $start_date );

		$query = "SELECT YEARWEEK(DATE_ADD(created_at, INTERVAL %f HOUR), 1) as yearweek, COUNT(*) as total
			 FROM {$wpdb->prefix}wdengage_entries
			 {$prefix} created_at >= %s
			 GROUP BY YEARWEEK(DATE_ADD(created_at, INTERVAL %f HOUR), 1)
			 ORDER BY yearweek ASC";

		$query_values = array_merge( [ $tz_offset ], $where['values'], [ $utc_start, $tz_offset ] );
		$results      = $wpdb->get_results( $wpdb->prepare( $query, $query_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		// Build week data map from results
		$week_data = [];
		foreach ( $results as $result ) {
			$week_data[ $result->yearweek ] = (int) $result->total;
		}

		$labels = [];
		$data   = [];

		// Generate last 8 weeks with proper labels
		for ( $i = 7; $i >= 0; $i-- ) {
			$week_start = new \DateTime( 'now', $wp_timezone );
			$week_start->modify( '-' . $i . ' weeks' );
			$week_start->modify( 'monday this week' );

			$yearweek = $week_start->format( 'oW' ); // ISO year + week
			$label    = $week_start->format( 'M j' );

			$labels[] = 'Week of ' . $label;
			$data[]   = isset( $week_data[ $yearweek ] ) ? $week_data[ $yearweek ] : 0;
		}

		return [
			'labels' => $labels,
			'datasets' => [
				[
					'label'           => __( 'Weekly Engagements', 'ultimate-spin-wheel' ),
					'data'            => $data,
					'backgroundColor' => 'rgba(34, 197, 94, 0.7)',
					'borderColor'     => 'rgb(34, 197, 94)',
					'borderWidth'     => 2,
					'borderRadius'    => 6,
				],
			],
		];
	}

	/**
	 * Get Prize Distribution
	 */
	private function get_prize_distribution( $filters = [] ) {
		global $wpdb;
		$filters['is_winner'] = true;

		$where = $this->get_where_clause( $filters );

		// Get all winner entries with their others_data
		$query = "SELECT others_data
			 FROM {$wpdb->prefix}wdengage_entries
			 {$where['clause']}";

		$results = $wpdb->get_results( $wpdb->prepare( $query, $where['values'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		// Aggregate by coupon_title in PHP (since we need to extract from JSON)
		$prize_counts = [];

		foreach ( $results as $result ) {
			$result_data  = json_decode( $result->others_data, true );
			$coupon_title = isset( $result_data['coupon_title'] ) && ! empty( $result_data['coupon_title'] )
				? $result_data['coupon_title']
				: 'Unknown Prize';

			if ( ! isset( $prize_counts[ $coupon_title ] ) ) {
				$prize_counts[ $coupon_title ] = 0;
			}
			++$prize_counts[ $coupon_title ];
		}

		// Sort by count descending and limit to top 10
		arsort( $prize_counts );
		$prize_counts = array_slice( $prize_counts, 0, 10, true );

		$labels = [];
		$data   = [];
		$colors = [
			'rgba(168, 85, 247, 0.8)',   // Purple
			'rgba(59, 130, 246, 0.8)',   // Blue
			'rgba(34, 197, 94, 0.8)',    // Green
			'rgba(249, 115, 22, 0.8)',   // Orange
			'rgba(239, 68, 68, 0.8)',    // Red
			'rgba(236, 72, 153, 0.8)',   // Pink
			'rgba(14, 165, 233, 0.8)',   // Sky
			'rgba(99, 102, 241, 0.8)',   // Indigo
			'rgba(16, 185, 129, 0.8)',   // Emerald
			'rgba(245, 158, 11, 0.8)',   // Amber
		];

		$color_index     = 0;
		$assigned_colors = [];

		foreach ( $prize_counts as $title => $count ) {
			$labels[]          = $title;
			$data[]            = $count;
			$assigned_colors[] = $colors[ $color_index % count( $colors ) ];
			++$color_index;
		}

		return [
			'labels' => $labels,
			'datasets' => [
				[
					'label'           => __( 'Prize Distribution', 'ultimate-spin-wheel' ),
					'data'            => $data,
					'backgroundColor' => $assigned_colors,
					'borderColor'     => array_map(function ( $c ) {
						return str_replace( '0.8', '1', $c );
					}, $assigned_colors),
					'borderWidth'     => 2,
					'borderRadius'    => 6,
				],
			],
		];
	}

	/**
	 * Get Top Coupons
	 */
	private function get_top_coupons( $filters = [] ) {
		global $wpdb;
		$filters['is_winner'] = true;

		$where = $this->get_where_clause( $filters );

		// Get all winner entries with their others_data
		$query = "SELECT others_data
			 FROM {$wpdb->prefix}wdengage_entries
			 {$where['clause']}";

		$results = $wpdb->get_results( $wpdb->prepare( $query, $where['values'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		// Aggregate by coupon_title in PHP (same as prize_distribution)
		$coupon_counts = [];
		$coupon_codes  = [];

		foreach ( $results as $result ) {
			$result_data  = json_decode( $result->others_data, true );
			$coupon_title = isset( $result_data['coupon_title'] ) && ! empty( $result_data['coupon_title'] )
				? $result_data['coupon_title']
				: 'Unknown';
			$coupon_code  = isset( $result_data['coupon_code'] ) ? $result_data['coupon_code'] : 'N/A';

			if ( ! isset( $coupon_counts[ $coupon_title ] ) ) {
				$coupon_counts[ $coupon_title ] = 0;
				$coupon_codes[ $coupon_title ]  = $coupon_code;
			}
			++$coupon_counts[ $coupon_title ];
		}

		// Sort by count descending and limit to top 5
		arsort( $coupon_counts );
		$coupon_counts = array_slice( $coupon_counts, 0, 5, true );

		$coupons = [];
		foreach ( $coupon_counts as $title => $count ) {
			$coupons[] = [
				'title' => $title,
				'code'  => isset( $coupon_codes[ $title ] ) ? $coupon_codes[ $title ] : 'N/A',
				'count' => $count,
			];
		}

		return $coupons;
	}

	/**
	 * Get Recent Activities
	 */
	private function get_recent_activities( $filters = [] ) {
		global $wpdb;

		$where = $this->get_where_clause( $filters );
		$query = "SELECT name, email, campaign_title, others_data, created_at
				 FROM {$wpdb->prefix}wdengage_entries 
				 {$where['clause']}
				 ORDER BY created_at DESC 
				 LIMIT %d";

		$query_values = array_merge( $where['values'], [ 10 ] );
		$results      = $wpdb->get_results( $wpdb->prepare( $query, $query_values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$activities = [];

		foreach ( $results as $result ) {
			$result_data = json_decode( $result->others_data, true );
			$is_winner   = ( isset( $result_data['status'] ) && ( $result_data['status'] === 'wins' || $result_data['status'] === 'won' ) ) || ! empty( $result_data['coupon_code'] );

			$activities[] = [
				'name'           => $result->name,
				'email'          => $result->email,
				'campaign'       => $result->campaign_title,
				'prize'          => $is_winner ? ( isset( $result_data['coupon_title'] ) ? $result_data['coupon_title'] : 'Prize' ) : 'No Prize',
				'code'           => $is_winner ? ( isset( $result_data['coupon_code'] ) ? $result_data['coupon_code'] : '' ) : '',
				'status'         => $is_winner ? 'Won' : 'Lost',
				'date'           => $result->created_at,
				'date_formatted' => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $result->created_at ) ),
			];
		}

		return $activities;
	}

	/**
	 * Build WHERE clause consistently
	 */
	private function get_where_clause( $filters = [] ) {
		global $wpdb;
		$where_conditions = [];
		$where_values     = [];

		// Filters are now handled via hook (Pro feature)
		$filters_data = apply_filters( 'ultimate_spin_wheel_report_filters', [
			'conditions' => $where_conditions,
			'values'     => $where_values,
		], $filters );

		$where_conditions = $filters_data['conditions'];
		$where_values     = $filters_data['values'];

		if ( isset( $filters['is_winner'] ) ) {
			if ( $filters['is_winner'] ) {
				$where_conditions[] = '(others_data LIKE %s OR others_data LIKE %s OR (others_data LIKE %s AND others_data NOT LIKE %s))';
				$where_values[]     = '%"status":"wins"%';
				$where_values[]     = '%"status":"won"%';
				$where_values[]     = '%"coupon_code":"%';
				$where_values[]     = '%"coupon_code":""%';
			} else {
				$where_conditions[] = '(others_data LIKE %s OR others_data LIKE %s) AND others_data LIKE %s';
				$where_values[]     = '%"status":"lost"%';
				$where_values[]     = '%"status":"loss"%';
				$where_values[]     = '%"coupon_code":""%';
			}
		}

		// Date Filters (Convert Local to UTC)
		if ( ! empty( $filters['start_date'] ) ) {
			// Assume input is Y-m-d. Add start of day time.
			$local_start = $filters['start_date'] . ' 00:00:00';
			$utc_start   = get_gmt_from_date( $local_start );
			$where_conditions[] = 'created_at >= %s';
			$where_values[]     = $utc_start;
		}

		if ( ! empty( $filters['end_date'] ) ) {
			// Assume input is Y-m-d. Add end of day time.
			$local_end = $filters['end_date'] . ' 23:59:59';
			$utc_end   = get_gmt_from_date( $local_end );
			$where_conditions[] = 'created_at <= %s';
			$where_values[]     = $utc_end;
		}

		// Exclude Trash (Default)
		if ( ! isset( $filters['include_trash'] ) || ! $filters['include_trash'] ) {
			$where_conditions[] = '(status NOT LIKE %s OR status IS NULL)';
			$where_values[]     = '%"stage":"trash"%';
		}

		$clause = '';
		if ( ! empty( $where_conditions ) ) {
			$clause = 'WHERE ' . implode( ' AND ', $where_conditions );
		}

		return [
			'clause' => $clause,
			'values' => $where_values,
		];
	}

	/**
	 * Format Chart Data Helper
	 */
	private function format_chart_data( $results, $label, $color ) {
		$date_labels = [];
		$data        = [];

		// Generate last 30 days
		for ( $i = 29; $i >= 0; $i-- ) {
			// Use WordPress current_datetime() for accurate local labels
			$date          = current_datetime()->modify( '-' . $i . ' days' )->format( 'Y-m-d' );
			$date_labels[] = wp_date( 'M j', strtotime( $date ) ); // Re-format using wp_date for localization
			$data[]        = 0; // Initialize with 0
		}

		// Fill in actual data
		foreach ( $results as $result ) {
			$index = array_search( wp_date( 'M j', strtotime( $result->date ) ), $date_labels );
			if ( false !== $index ) {
				$data[ $index ] = (int) $result->total;
			}
		}

		return [
			'labels' => $date_labels,
			'datasets' => [
				[
					'label'           => $label,
					'data'            => $data,
					'borderColor'     => $color,
					'backgroundColor' => $color,
					'tension'         => 0.3,
					'fill'            => true,
				],
			],
		];
	}

	/**
	 * Get Random Color
	 */
	private function get_random_color() {
		$colors = [
			'rgba(59, 130, 246, 0.8)',   // Blue
			'rgba(34, 197, 94, 0.8)',    // Green
			'rgba(168, 85, 247, 0.8)',   // Purple
			'rgba(249, 115, 22, 0.8)',   // Orange
			'rgba(239, 68, 68, 0.8)',    // Red
			'rgba(236, 72, 153, 0.8)',   // Pink
			'rgba(14, 165, 233, 0.8)',   // Sky
			'rgba(99, 102, 241, 0.8)',   // Indigo
			'rgba(16, 185, 129, 0.8)',   // Emerald
			'rgba(245, 158, 11, 0.8)',   // Amber
		];

		return $colors[ array_rand( $colors ) ];
	}

	/**
	 * Get Creative Insights
	 */
	private function get_insights( $filters = [] ) {
		global $wpdb;

		$wp_timezone    = wp_timezone();
		$now            = new \DateTime( 'now', $wp_timezone );
		$offset_seconds = $wp_timezone->getOffset( $now );
		$offset_hours   = $offset_seconds / 3600;

		$day_names = [ 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ];

		// Get where clause for campaign filtering
		$where        = $this->get_where_clause( $filters );
		$where_clause = ! empty( $where['clause'] ) ? $where['clause'] : 'WHERE 1=1';
		$base_prefix  = ! empty( $where['clause'] ) ? $where['clause'] . ' AND ' : 'WHERE ';

		// Best Performing Day (using filter or last 30 days)
		$date_filter  = ! empty( $filters['start_date'] ) ? '' : 'AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
		$best_day_sql = "SELECT DAYOFWEEK(DATE_ADD(created_at, INTERVAL %d HOUR)) as day, COUNT(*) as total
			 FROM {$wpdb->prefix}wdengage_entries
			 {$base_prefix} 1=1 {$date_filter}
			 GROUP BY DAYOFWEEK(DATE_ADD(created_at, INTERVAL %d HOUR))
			 ORDER BY total DESC
			 LIMIT 1";

		$best_day_query  = $wpdb->prepare( $best_day_sql, array_merge( [ $offset_hours ], $where['values'], [ $offset_hours ] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$best_day_result = $wpdb->get_row( $best_day_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$best_day        = $best_day_result ? $day_names[ (int) $best_day_result->day - 1 ] : 'N/A';
		$best_day_count  = $best_day_result ? (int) $best_day_result->total : 0;

		// Golden Hour (best performing hour - using filter or last 7 days)
		$hour_date_filter = ! empty( $filters['start_date'] ) ? '' : 'AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
		$golden_hour_sql  = "SELECT HOUR(DATE_ADD(created_at, INTERVAL %d HOUR)) as hour, COUNT(*) as total
			 FROM {$wpdb->prefix}wdengage_entries
			 {$base_prefix} 1=1 {$hour_date_filter}
			 GROUP BY HOUR(DATE_ADD(created_at, INTERVAL %d HOUR))
			 ORDER BY total DESC
			 LIMIT 1";

		$golden_hour_query  = $wpdb->prepare( $golden_hour_sql, array_merge( [ $offset_hours ], $where['values'], [ $offset_hours ] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$golden_hour_result = $wpdb->get_row( $golden_hour_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$golden_hour        = 'N/A';
		$golden_hour_count  = 0;
		if ( $golden_hour_result ) {
			$h                 = (int) $golden_hour_result->hour;
			$golden_hour       = $h === 0 ? '12 AM' : ( $h < 12 ? $h . ' AM' : ( $h === 12 ? '12 PM' : ( $h - 12 ) . ' PM' ) );
			$golden_hour_count = (int) $golden_hour_result->total;
		}

		// This Week Spins (with campaign filter)
		$this_week_sql   = "SELECT COUNT(*) as total FROM {$wpdb->prefix}wdengage_entries 
			{$base_prefix} created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
		$this_week_query = $wpdb->prepare( $this_week_sql, $where['values'] ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$this_week       = (int) $wpdb->get_var( $this_week_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		// Average Daily Spins (with campaign filter, last 30 days or filter range)
		$avg_date_filter  = ! empty( $filters['start_date'] ) ? '' : 'AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
		$avg_daily_sql    = "SELECT COUNT(*) as total, COUNT(DISTINCT DATE(created_at)) as days 
			FROM {$wpdb->prefix}wdengage_entries 
			{$base_prefix} 1=1 {$avg_date_filter}";
		$avg_daily_query  = $wpdb->prepare( $avg_daily_sql, $where['values'] ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$avg_daily_result = $wpdb->get_row( $avg_daily_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$avg_daily        = 0;
		if ( $avg_daily_result && $avg_daily_result->days > 0 ) {
			$avg_daily = round( $avg_daily_result->total / $avg_daily_result->days );
		}

		// Active Days (with campaign filter, last 30 days or filter range)
		$active_days_sql   = "SELECT COUNT(DISTINCT DATE(created_at)) as days 
			FROM {$wpdb->prefix}wdengage_entries 
			{$base_prefix} 1=1 {$avg_date_filter}";
		$active_days_query = $wpdb->prepare( $active_days_sql, $where['values'] ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$active_days       = (int) $wpdb->get_var( $active_days_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		return [
			'best_day'          => $best_day,
			'best_day_count'    => $best_day_count,
			'golden_hour'       => $golden_hour,
			'golden_hour_count' => $golden_hour_count,
			'this_week_spins'   => $this_week,
			'avg_daily_spins'   => $avg_daily,
			'active_days'       => $active_days,
		];
	}
}
