<?php

namespace USPIN_WHEEL\Modules\SpinWheel;

defined( 'ABSPATH' ) || exit;

class Reports {
	private static $instance = null;

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'wp_ajax_ultimate_spin_wheel_reports', array( $this, 'reports' ) );
		add_action( 'wp_ajax_nopriv_ultimate_spin_wheel_reports', array( $this, 'reports' ) );
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
		// TODO: Implement nonce verification for security in future updates

		$reports = array(
			'total_engagements'         => $this->get_total_engagements(),
			'total_winners'             => $this->get_total_winners(),
			'total_losers'              => $this->get_total_losers(),
			'total_leads'               => $this->get_total_leads(),
			'today_engagements'         => $this->get_today_engagements(),
			'today_winners'             => $this->get_today_winners(),
			'today_losers'              => $this->get_today_losers(), // Added total losers for today
			'today_leads'               => $this->get_today_leads(),
			'conversion_rate'           => $this->get_conversion_rate(),
			'win_rate'                  => $this->get_win_rate(),
			'last_30_days_engagements'  => $this->last_30_days_engagements(),
			'last_30_days_winners'      => $this->last_30_days_winners(),
			'last_30_days_leads'        => $this->last_30_days_leads(),
			'engagement_by_campaigns'   => $this->get_engagement_by_campaigns(),
			'winners_by_campaigns'      => $this->get_winners_by_campaigns(),
			'monthly_trends'            => $this->get_monthly_trends(),
			'hourly_engagement_pattern' => $this->get_hourly_engagement_pattern(),
			'days_engagement_pattern'   => $this->get_days_engagement_pattern(),
			'weekly_engagement_pattern' => $this->get_weekly_engagement_pattern(),
			'prize_distribution'        => $this->get_prize_distribution(),
			'top_coupons'               => $this->get_top_coupons(),
			'recent_activities'         => $this->get_recent_activities(),
		);

		wp_send_json_success( $reports );
	}

	/**
	 * Get Total Engagements
	 */
	private function get_total_engagements() {
		global $wpdb;
		$query = "SELECT COUNT(*) FROM {$wpdb->prefix}wdengage_entries";
		// phpcs:ignore
		return (int) $wpdb->get_var( $query );
	}

	/**
	 * Get Total Winners
	 */
	private function get_total_winners() {
		global $wpdb;
    // phpcs:ignore
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}wdengage_entries WHERE others_data LIKE %s",
			'%"status":"won"%'
		) );
	}

	/**
	 * Get Total Losers
	 */
	private function get_total_losers() {
		global $wpdb;
		$query = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}wdengage_entries WHERE others_data LIKE %s OR others_data NOT LIKE %s",
			'%"status":"lost"%',
			'%"status":"won"%'
		);
     // phpcs:ignore
		return (int) $wpdb->get_var( $query );
	}

	/**
	 * Get Total Leads (unique emails)
	 */
	private function get_total_leads() {
		global $wpdb;
		$query = "SELECT COUNT(DISTINCT email) FROM {$wpdb->prefix}wdengage_entries WHERE email != '' AND email IS NOT NULL";
		// phpcs:ignore
		return (int) $wpdb->get_var( $query );
	}

	/**
	 * Get Today's Engagements
	 */
	private function get_today_engagements() {
		global $wpdb;
		$query = "SELECT COUNT(*) FROM {$wpdb->prefix}wdengage_entries WHERE DATE(created_at) = CURDATE()";
		// phpcs:ignore
		return (int) $wpdb->get_var( $query );
	}

	/**
	 * Get Today's Winners
	 */
	private function get_today_winners() {
		global $wpdb;
		$query = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}wdengage_entries WHERE others_data LIKE %s AND DATE(created_at) = CURDATE()",
			'%"status":"won"%'
		);
     // phpcs:ignore
		return (int) $wpdb->get_var( $query );
	}

	/**
	 * Get Today's Losers
	 */
	private function get_today_losers() {
		global $wpdb;
		$query = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}wdengage_entries WHERE (others_data LIKE %s OR others_data NOT LIKE %s) AND DATE(created_at) = CURDATE()",
			'%"status":"lost"%',
			'%"status":"won"%'
		);
     // phpcs:ignore
		return (int) $wpdb->get_var( $query );
	}

	/**
	 * Get Today's Leads
	 */
	private function get_today_leads() {
		global $wpdb;
		$query = "SELECT COUNT(DISTINCT email) FROM {$wpdb->prefix}wdengage_entries WHERE email != '' AND email IS NOT NULL AND DATE(created_at) = CURDATE()";
		// phpcs:ignore
		return (int) $wpdb->get_var( $query );
	}

	/**
	 * Get Conversion Rate (Winners/Total Engagements)
	 */
	private function get_conversion_rate() {
		$total = $this->get_total_engagements();
		$winners = $this->get_total_winners();
		return $total > 0 ? round( ( $winners / $total ) * 100, 2 ) : 0;
	}

	/**
	 * Get Win Rate (Winners/Total Engagements)
	 */
	private function get_win_rate() {
		return $this->get_conversion_rate();
	}

	/**
	 * Last 30 Days Engagements Data
	 */
	public function last_30_days_engagements() {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT COUNT(*) as total, DATE(created_at) as date
			 FROM {$wpdb->prefix}wdengage_entries
			 WHERE created_at >= CURDATE() - INTERVAL %d DAY
			 GROUP BY DATE(created_at)
			 ORDER BY date ASC",
			29
		);

		// phpcs:ignore
		$results = $wpdb->get_results( $query );

		return $this->format_chart_data( $results, 'Last 30 Days Engagements', 'rgba(59, 130, 246, 0.8)' );
	}

	/**
	 * Last 30 Days Winners Data
	 */
	public function last_30_days_winners() {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT COUNT(*) as total, DATE(created_at) as date
			 FROM {$wpdb->prefix}wdengage_entries
			 WHERE others_data LIKE %s AND created_at >= CURDATE() - INTERVAL %d DAY
			 GROUP BY DATE(created_at)
			 ORDER BY date ASC",
			'%"status":"won"%',
			29
		);

		// phpcs:ignore
		$results = $wpdb->get_results( $query );

		return $this->format_chart_data( $results, 'Last 30 Days Winners', 'rgba(34, 197, 94, 0.8)' );
	}

	/**
	 * Last 30 Days Leads Data
	 */
	public function last_30_days_leads() {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT COUNT(DISTINCT email) as total, DATE(created_at) as date
			 FROM {$wpdb->prefix}wdengage_entries
			 WHERE email != '' AND email IS NOT NULL AND created_at >= CURDATE() - INTERVAL %d DAY
			 GROUP BY DATE(created_at)
			 ORDER BY date ASC",
			29
		);

		// phpcs:ignore
		$results = $wpdb->get_results( $query );

		return $this->format_chart_data( $results, 'Last 30 Days Leads', 'rgba(168, 85, 247, 0.8)' );
	}

	/**
	 * Get Engagement by Campaigns
	 */
	private function get_engagement_by_campaigns() {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT campaign_title, COUNT(*) as total
			 FROM {$wpdb->prefix}wdengage_entries
			 GROUP BY campaign_title
			 ORDER BY total DESC
			 LIMIT %d",
			10
		);

		// phpcs:ignore
		$results = $wpdb->get_results( $query );

		$labels = array();
		$data = array();
		$colors = array();

		foreach ( $results as $result ) {
			$labels[] = $result->campaign_title ? $result->campaign_title : 'Unknown Campaign';
			$data[] = (int) $result->total;
			$colors[] = $this->get_random_color();
		}

		return array(
			'labels'   => $labels,
			'datasets' => array(
				array(
					'label'           => 'Engagements by Campaign',
					'data'            => $data,
					'backgroundColor' => $colors,
					'borderWidth'     => 1,
				),
			),
		);
	}

	/**
	 * Get Winners by Campaigns
	 */
	private function get_winners_by_campaigns() {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT campaign_title, COUNT(*) as total
			 FROM {$wpdb->prefix}wdengage_entries
			 WHERE others_data LIKE %s
			 GROUP BY campaign_title
			 ORDER BY total DESC
			 LIMIT %d",
			'%"status":"won"%',
			10
		);

		// phpcs:ignore
		$results = $wpdb->get_results( $query );

		$labels = array();
		$data = array();
		$colors = array();

		foreach ( $results as $result ) {
			$labels[] = $result->campaign_title ? $result->campaign_title : 'Unknown Campaign';
			$data[] = (int) $result->total;
			$colors[] = $this->get_random_color();
		}

		return array(
			'labels'   => $labels,
			'datasets' => array(
				array(
					'label'           => 'Winners by Campaign',
					'data'            => $data,
					'backgroundColor' => $colors,
					'borderWidth'     => 1,
				),
			),
		);
	}

	/**
	 * Get Monthly Trends (Last 12 Months)
	 */
	private function get_monthly_trends() {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT 
				COUNT(*) as total_engagements,
				COUNT(CASE WHEN others_data LIKE %s THEN 1 END) as total_winners,
				COUNT(DISTINCT email) as total_leads,
				DATE_FORMAT(created_at, '%%Y-%%m') as month
			 FROM {$wpdb->prefix}wdengage_entries
			 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL %d MONTH)
			 GROUP BY DATE_FORMAT(created_at, '%%Y-%%m')
			 ORDER BY month ASC",
			'%"status":"won"%',
			12
		);

		// phpcs:ignore
		$results = $wpdb->get_results( $query );

		$labels = array();
		$engagements = array();
		$winners = array();
		$leads = array();

		foreach ( $results as $result ) {
			$labels[] = gmdate( 'M Y', strtotime( $result->month . '-01' ) );
			$engagements[] = (int) $result->total_engagements;
			$winners[] = (int) $result->total_winners;
			$leads[] = (int) $result->total_leads;
		}

		return array(
			'labels'   => $labels,
			'datasets' => array(
				array(
					'label'           => 'Total Engagements',
					'data'            => $engagements,
					'borderColor'     => 'rgb(59, 130, 246)',
					'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
					'tension'         => 0.3,
				),
				array(
					'label'           => 'Winners',
					'data'            => $winners,
					'borderColor'     => 'rgb(34, 197, 94)',
					'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
					'tension'         => 0.3,
				),
				array(
					'label'           => 'Leads',
					'data'            => $leads,
					'borderColor'     => 'rgb(168, 85, 247)',
					'backgroundColor' => 'rgba(168, 85, 247, 0.1)',
					'tension'         => 0.3,
				),
			),
		);
	}

	/**
	 * Get Hourly Engagement Pattern
	 */
	private function get_hourly_engagement_pattern() {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT HOUR(created_at) as hour, COUNT(*) as total
			 FROM {$wpdb->prefix}wdengage_entries
			 WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
			 GROUP BY HOUR(created_at)
			 ORDER BY hour ASC",
			7
		);

		// phpcs:ignore
		$results = $wpdb->get_results( $query );

		$labels = array();
		$data = array();

		// Initialize all hours with 0
		for ( $i = 0; $i < 24; $i++ ) {
			$labels[] = sprintf( '%02d:00', $i );
			$data[] = 0;
		}

		// Fill in actual data
		foreach ( $results as $result ) {
			$data[ (int) $result->hour ] = (int) $result->total;
		}

		return array(
			'labels'   => $labels,
			'datasets' => array(
				array(
					'label'           => 'Hourly Engagement Pattern',
					'data'            => $data,
					'backgroundColor' => 'rgba(249, 115, 22, 0.6)',
					'borderColor'     => 'rgb(249, 115, 22)',
					'borderWidth'     => 2,
				),
			),
		);
	}

	/**
	 * Get Days Engagement Pattern
	 */
	private function get_days_engagement_pattern() {
		global $wpdb;
		$query = $wpdb->prepare(
			"SELECT DAYOFWEEK(created_at) as day, COUNT(*) as total
			 FROM {$wpdb->prefix}wdengage_entries
			 WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
			 GROUP BY DAYOFWEEK(created_at)
			 ORDER BY day ASC",
			30
		);
		// phpcs:ignore
		$results = $wpdb->get_results( $query );
		$labels = array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat' );
		$data = array_fill( 0, 7, 0 ); // Initialize all days with 0
		foreach ( $results as $result ) {
			$day_index = (int) $result->day - 1; // DAYOFWEEK returns 1 for Sunday, 2 for Monday, etc.
			if ( $day_index >= 0 && $day_index < 7 ) {
				$data[ $day_index ] = (int) $result->total;
			}
		}
		return array(
			'labels'   => $labels,
			'datasets' => array(
				array(
					'label'           => 'Day\'s Engagement Pattern',
					'data'            => $data,
					'backgroundColor' => 'rgba(236, 72, 153, 0.6)',
					'borderColor'     => 'rgb(236, 72, 153)',
					'borderWidth'     => 2,
				),
			),
		);
	}

	/**
	 * Get Weekly Engagement Pattern (Last 8 Weeks)
	 */
	private function get_weekly_engagement_pattern() {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT WEEK(created_at) as week, YEAR(created_at) as year, COUNT(*) as total
			 FROM {$wpdb->prefix}wdengage_entries
			 WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d WEEK)
			 GROUP BY YEAR(created_at), WEEK(created_at)
			 ORDER BY year ASC, week ASC",
			8
		);

		// phpcs:ignore
		$results = $wpdb->get_results( $query );

		$labels = array();
		$data = array();

		// Generate last 8 weeks
		for ( $i = 7; $i >= 0; $i-- ) {
			$week_start = gmdate( 'M j', strtotime( '-' . $i . ' weeks' ) );
			$week_end = gmdate( 'M j', strtotime( '-' . $i . ' weeks + 6 days' ) );
			$labels[] = $week_start . ' - ' . $week_end;
			$data[] = 0; // Initialize with 0
		}

		// Fill in actual data
		foreach ( $results as $result ) {
			$week_start_date = gmdate( 'Y-m-d', strtotime( $result->year . 'W' . sprintf( '%02d', $result->week ) ) );
			$weeks_ago = (int) ( ( strtotime( 'now' ) - strtotime( $week_start_date ) ) / ( 7 * 24 * 60 * 60 ) );

			if ( $weeks_ago >= 0 && $weeks_ago < 8 ) {
				$index = 7 - $weeks_ago;
				if ( $index >= 0 && $index < 8 ) {
					$data[ $index ] = (int) $result->total;
				}
			}
		}

		return array(
			'labels'   => $labels,
			'datasets' => array(
				array(
					'label'           => 'Weekly Engagement Pattern',
					'data'            => $data,
					'backgroundColor' => 'rgba(34, 197, 94, 0.6)',
					'borderColor'     => 'rgb(34, 197, 94)',
					'borderWidth'     => 2,
				),
			),
		);
	}

	/**
	 * Get Prize Distribution
	 */
	private function get_prize_distribution() {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT others_data, COUNT(*) as total
			 FROM {$wpdb->prefix}wdengage_entries
			 WHERE others_data LIKE %s
			 GROUP BY others_data
			 ORDER BY total DESC
			 LIMIT %d",
			'%"status":"won"%',
			10
		);

		// phpcs:ignore
		$results = $wpdb->get_results( $query );

		$labels = array();
		$data = array();
		$colors = array();

		foreach ( $results as $result ) {
			$result_data = json_decode( $result->others_data, true );
			$coupon_title = isset( $result_data['coupon_title'] ) ? $result_data['coupon_title'] : 'Unknown Prize';

			$labels[] = $coupon_title;
			$data[] = (int) $result->total;
			$colors[] = $this->get_random_color();
		}

		return array(
			'labels'   => $labels,
			'datasets' => array(
				array(
					'label'           => 'Prize Distribution',
					'data'            => $data,
					'backgroundColor' => $colors,
					'borderWidth'     => 1,
				),
			),
		);
	}

	/**
	 * Get Top Coupons
	 */
	private function get_top_coupons() {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT others_data, COUNT(*) as total
			 FROM {$wpdb->prefix}wdengage_entries
			 WHERE others_data LIKE %s
			 GROUP BY others_data
			 ORDER BY total DESC
			 LIMIT %d",
			'%"status":"won"%',
			5
		);

		// phpcs:ignore
		$results = $wpdb->get_results( $query );

		$coupons = array();

		foreach ( $results as $result ) {
			$result_data = json_decode( $result->others_data, true );
			$coupons[] = array(
				'title' => isset( $result_data['coupon_title'] ) ? $result_data['coupon_title'] : 'Unknown',
				'code'  => isset( $result_data['coupon_code'] ) ? $result_data['coupon_code'] : 'N/A',
				'count' => (int) $result->total,
			);
		}

		return $coupons;
	}

	/**
	 * Get Recent Activities
	 */
	private function get_recent_activities() {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT name, email, campaign_title, others_data, created_at
			 FROM {$wpdb->prefix}wdengage_entries
			 ORDER BY created_at DESC
			 LIMIT %d",
			10
		);

		// phpcs:ignore
		$results = $wpdb->get_results( $query );

		$activities = array();

		foreach ( $results as $result ) {
			$result_data = json_decode( $result->others_data, true );
			$is_winner = isset( $result_data['status'] ) && 'won' === $result_data['status'];

			$activities[] = array(
				'name'     => $result->name,
				'email'    => $result->email,
				'campaign' => $result->campaign_title,
				'prize'    => $is_winner ? ( isset( $result_data['coupon_title'] ) ? $result_data['coupon_title'] : 'Prize' ) : 'No Prize',
				'status'   => $is_winner ? 'Won' : 'Lost',
				'date'     => $result->created_at,
			);
		}

		return $activities;
	}

	/**
	 * Format Chart Data Helper
	 */
	private function format_chart_data( $results, $label, $color ) {
		$date_labels = array();
		$data = array();

		// Generate last 30 days
		for ( $i = 29; $i >= 0; $i-- ) {
			$date = gmdate( 'Y-m-d', strtotime( '-' . $i . ' days' ) );
			$date_labels[] = gmdate( 'M j', strtotime( $date ) );
			$data[] = 0; // Initialize with 0
		}

		// Fill in actual data
		foreach ( $results as $result ) {
			$index = array_search( gmdate( 'M j', strtotime( $result->date ) ), $date_labels );
			if ( false !== $index ) {
				$data[ $index ] = (int) $result->total;
			}
		}

		return array(
			'labels'   => $date_labels,
			'datasets' => array(
				array(
					'label'           => $label,
					'data'            => $data,
					'borderColor'     => $color,
					'backgroundColor' => $color,
					'tension'         => 0.3,
					'fill'            => true,
				),
			),
		);
	}

	/**
	 * Get Random Color
	 */
	private function get_random_color() {
		$colors = array(
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
		);

		return $colors[ array_rand( $colors ) ];
	}
}
