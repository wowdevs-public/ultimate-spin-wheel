<?php

namespace USPIN_WHEEL\Modules\SpinWheel;

defined( 'ABSPATH' ) || exit;

class Spin_Wheel {
	private static $instance = null;
	protected $post_type     = 'wowdevs_engage';
	private $settings        = null;

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'wp_ajax_ultimate_spin_wheel_sc_imp_count', array( $this, 'impression_count' ) );
		add_action( 'wp_ajax_nopriv_ultimate_spin_wheel_sc_imp_count', array( $this, 'impression_count' ) );

		// Add this AJAX handler in your PHP class
		add_action( 'wp_ajax_ultimate_spin_wheel_spinned', array( $this, 'spin_wheel_spinned' ) );
		add_action( 'wp_ajax_nopriv_ultimate_spin_wheel_spinned', array( $this, 'spin_wheel_spinned' ) );
		add_action(
			'wp',
			function () {
				$spin_wheel = $this->spin_wheel_init();
				if ( $spin_wheel ) {
					add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
					add_action( 'wp_footer', array( $this, 'render_spin_wheel' ) );
				}
			}
		);
	}
	public function spin_wheel_spinned() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimate_spin_wheel' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid nonce', 'ultimate-spin-wheel' ) ) );
		}

		$name           = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : null;
		$email          = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : null;
		$campaign_id    = isset( $_POST['campaign_id'] ) ? intval( wp_unslash( $_POST['campaign_id'] ) ) : 0;
		$campaign_title = isset( $_POST['campaign_title'] ) ? sanitize_text_field( wp_unslash( $_POST['campaign_title'] ) ) : null;
		$coupon_code    = isset( $_POST['coupon_code'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) : '';
		$coupon_title   = isset( $_POST['coupon_title'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_title'] ) ) : '';
		$status         = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : null;

		$defaults = array(
			'name'          => '',
			'email'         => '',
			'campaign_type' => 'Spin Wheel',
		);

		$args = array(
			'campaign_id'    => $campaign_id,
			'campaign_title' => $campaign_title,
			'name'           => $name,
			'email'          => $email,
			'others_data'    => json_encode(
				array(
					'coupon_title' => $coupon_title,
					'coupon_code'  => $coupon_code,
					'status'       => $status,
				)
			),
			'user_data'      => json_encode(
				array(
					'ip_address' => $this->get_user_ip(),
					'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
				)
			),
		);

		$data = wp_parse_args( $args, $defaults );

		global $wpdb;
		$table_name = $wpdb->prefix . 'wdengage_entries';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table requires direct database query.
		$wpdb->insert( $table_name, $data );

		if ( $wpdb->last_error ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Error saving data', 'ultimate-spin-wheel' ) ) );
		} else {
			wp_send_json_success( array( 'message' => esc_html__( 'Data saved successfully', 'ultimate-spin-wheel' ) ) );
		}
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
	 * Handle spin wheel AJAX request
	 */
	public function spin_wheel_init() {
		/**
	 * Check if spin wheel is active
	 */
		if ( ! $this->is_spin_wheel_active() ) {
			return false;
		}

		$post_data = $this->is_spin_wheel_active();
		$post_id   = $post_data->post_id ?? 0;
		$meta      = $post_data->meta ?? array();

		$display_on          = json_decode( $meta['uspw_display_on'][0] ) ?? array();
		$not_display_on      = json_decode( $meta['uspw_not_display_on'][0] ) ?? array();
		$display_special     = json_decode( $meta['uspw_display_special_pages'][0] ) ?? array();
		$not_display_special = json_decode( $meta['uspw_not_display_special_pages'][0] ) ?? array();
		$display_custom      = json_decode( $meta['uspw_display_custom_pages'][0] ) ?? array();
		$not_display_custom  = json_decode( $meta['uspw_not_display_custom_pages'][0] ) ?? array();
		$display_roles       = json_decode( $meta['uspw_display_roles'][0] ) ?? array();

		$should_display = $this->should_display_conditions( $display_on, $not_display_on, $display_special, $not_display_special, $display_custom, $not_display_custom, $display_roles );

		if ( isset( $_GET['spin_wheel'] ) && isset( $_GET['_wpnonce'] ) && 'preview' === sanitize_text_field( wp_unslash( $_GET['spin_wheel'] ) ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'ultimate_spin_wheel' ) ) {
			$should_display = true; // Always display in preview mode
			$post_id        = isset( $_GET['campaign_id'] ) ? intval( wp_unslash( $_GET['campaign_id'] ) ) : $post_id;
			$meta           = get_post_meta( $post_id ); // Get post meta for preview
			return array(
				'post_id' => $post_id,
				'meta'    => $meta,
			);
		}

		if ( isset( $_GET['spin_wheel'] ) && isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'ultimate_spin_wheel' ) ) {
			return false; // Invalid nonce, do not display
		}

		if ( ! $should_display ) {
			return false;
		}

		return array(
			'post_id' => $post_id,
			'meta'    => $meta,
		);
	}

	/**
	 * Check if the spin wheel feature is currently active.
	 */
	private function is_spin_wheel_active() {
		// Get the current spin wheel post/template
		$spin_wheel_post = $this->get_current_spin_wheel_post();

		if ( ! $spin_wheel_post ) {
			return false;
		}

		/**
		 * Max Impressions Check
		 */
		$max_impressions   = isset( $spin_wheel_post->meta['uspw_max_impressions'] ) ? intval( $spin_wheel_post->meta['uspw_max_impressions'][0] ) : 0;
		$users_impressions = isset( $spin_wheel_post->meta['uspw_impressions_count'] ) ? intval( $spin_wheel_post->meta['uspw_impressions_count'][0] ) : 0;

		if ( 0 !== $max_impressions && $users_impressions > $max_impressions ) {
			return false;
		}

		/**
			 * Get user name and email
			 */
		$user_name  = is_user_logged_in() ? wp_get_current_user()->display_name : '';
		$user_email = is_user_logged_in() ? wp_get_current_user()->user_email : '';

		$this->settings = array(
			'post_id'        => $spin_wheel_post->post_id,
			'post_title'     => get_the_title( $spin_wheel_post->post_id ),
			'user_ip'        => $this->get_user_ip(),
			'user_name'      => $user_name,
			'email'          => $user_email,
			'collect_email'  => isset( $spin_wheel_post->meta['uspw_collect_email'] ) ? $spin_wheel_post->meta['uspw_collect_email'] : true,
			'popup_settings' => isset( $spin_wheel_post->meta['uspw_popup_settings'] ) ? json_decode( $spin_wheel_post->meta['uspw_popup_settings'][0], true ) : array(),
		);

		return $spin_wheel_post;
	}

	/**
	 * Get the current spin wheel post based on conditions
	 * Improved version with better post selection logic
	 */
	private function get_current_spin_wheel_post() {
		$args = array(
			'post_type'      => $this->post_type,
			'posts_per_page' => -1, // Get all matching posts for better selection
			'post_status'    => 'publish',
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => 'uspw_type',
					'value'   => 'spin_wheel',
					'compare' => '=',
				),
			),
			'orderby'        => 'date',
			'order'          => 'DESC', // Most recent first
		);

		/**
		 * Preview mode: if campaign_id is set, fetch that specific post
		 */
		if ( isset( $_GET['campaign_id'] ) && intval( wp_unslash( $_GET['campaign_id'] ) ) > 0 && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'ultimate_spin_wheel' ) ) {
			$args['p']              = intval( wp_unslash( $_GET['campaign_id'] ) );
			$args['posts_per_page'] = 1; // Only need one specific post
		}

		/**
		* If not preview mode, then add meta query to check for enabled status and date range
		*/
		if ( ! isset( $_GET['campaign_id'] ) || intval( wp_unslash( $_GET['campaign_id'] ) ) <= 0 ) {
			$args['meta_query'][] = array(
				'key'     => 'uspw_status',
				'value'   => 'enabled',
				'compare' => '=',
			);
			$args['meta_query'][] = array(
				'key'     => 'uspw_start_date',
				'value'   => current_time( 'mysql' ),
				'compare' => '<=',
			);
			$args['meta_query'][] = array(
				'key'     => 'uspw_end_date',
				'value'   => current_time( 'mysql' ),
				'compare' => '>=',
			);
		}

		$posts = get_posts( $args );

		if ( empty( $posts ) ) {
			return null;
		}

		// If preview mode or only one post, return it directly
		if ( count( $posts ) === 1 ) {
			$post          = $posts[0];
			$meta          = get_post_meta( $post->ID );
			$filtered_meta = $this->filter_meta_keys( $meta );
			wp_reset_postdata();
			return (object) array(
				'post_id' => $post->ID,
				'meta'    => $filtered_meta,
			);
		}

		// Multiple posts found - apply selection logic
		$selected_post = $this->select_best_matching_post( $posts );

		if ( $selected_post ) {
			$meta          = get_post_meta( $selected_post->ID );
			$filtered_meta = $this->filter_meta_keys( $meta );
			wp_reset_postdata();
			return (object) array(
				'post_id' => $selected_post->ID,
				'meta'    => $filtered_meta,
			);
		}

		return null;
	}

	/**
	Select the best matching post from multiple candidates

	@param array $posts Array of WP_Post objects
	@return WP_Post|null The best matching post or null
	 */
	private function select_best_matching_post( $posts ) {
		global $post;

		$scored_posts = array();

		foreach ( $posts as $candidate_post ) {
			$meta  = get_post_meta( $candidate_post->ID );
			$score = 0;

			// Get display conditions
			$display_on          = json_decode( $meta['uspw_display_on'][0] ?? '[]', true );
			$not_display_on      = json_decode( $meta['uspw_not_display_on'][0] ?? '[]', true );
			$display_special     = json_decode( $meta['uspw_display_special_pages'][0] ?? '[]', true );
			$not_display_special = json_decode( $meta['uspw_not_display_special_pages'][0] ?? '[]', true );
			$display_custom      = json_decode( $meta['uspw_display_custom_pages'][0] ?? '[]', true );
			$not_display_custom  = json_decode( $meta['uspw_not_display_custom_pages'][0] ?? '[]', true );
			$display_roles       = json_decode( $meta['uspw_display_roles'][0] ?? '[]', true );

			// Check if this post should be displayed
			$should_display = $this->should_display_conditions(
				$display_on,
				$not_display_on,
				$display_special,
				$not_display_special,
				$display_custom,
				$not_display_custom,
				$display_roles
			);

			// Skip posts that shouldn't be displayed
			if ( ! $should_display ) {
					continue;
			}

			// Scoring logic - higher score = better match

			// 1. Specific page targeting gets highest priority
			if ( $post && in_array( 'custom_pages', $display_on ) && in_array( $post->ID, $display_custom ) ) {
				$score += 100; // Highest priority for specific page targeting
			}

			// 2. Special page targeting gets high priority
			if ( is_front_page() && in_array( 'front_page', $display_special ) ) {
				$score += 80;
			}
			if ( is_home() && in_array( 'blog_page', $display_special ) ) {
				$score += 80;
			}
			if ( is_archive() && in_array( 'archive_page', $display_special ) ) {
				$score += 80;
			}
			if ( is_404() && in_array( '404_page', $display_special ) ) {
				$score += 80;
			}

			// 3. Content type targeting gets medium priority
			if ( is_page() && in_array( 'all_pages', $display_on ) ) {
				$score += 60;
			}
			if ( is_single() && in_array( 'all_posts', $display_on ) ) {
				$score += 60;
			}

			// 4. Entire site gets lowest priority (most generic)
			if ( in_array( 'entire_site', $display_on ) ) {
				$score += 20;
			}

			// 5. Role-based targeting adds bonus points
			if ( ! empty( $display_roles ) ) {
				$user = wp_get_current_user();
				if ( is_user_logged_in() && in_array( 'logged_in', $display_roles ) ) {
					$score += 10;
				}
				if ( ! is_user_logged_in() && in_array( 'logged_out', $display_roles ) ) {
					$score += 10;
				}
				// Check specific roles
				$user_roles = array_diff( $display_roles, array( 'logged_in', 'logged_out' ) );
				if ( ! empty( $user_roles ) && ! empty( array_intersect( $user->roles, $user_roles ) ) ) {
					$score += 15;
				}
			}

			// 6. Priority based on post date (newer posts get slight bonus)
			$post_date = strtotime( $candidate_post->post_date );
			$days_old  = ( time() - $post_date ) / ( 60 * 60 * 24 );
			if ( $days_old < 7 ) {
				$score += 5; // Bonus for posts less than a week old
			}

			// 7. Check impression limits (posts with available impressions get bonus)
			$max_impressions     = intval( $meta['uspw_max_impressions'][0] ?? 0 );
			$current_impressions = intval( $meta['uspw_impressions_count'][0] ?? 0 );

			if ( $max_impressions > 0 ) {
				$remaining_impressions = $max_impressions - $current_impressions;
				if ( $remaining_impressions > 0 ) {
					$score += min( $remaining_impressions / $max_impressions * 10, 10 ); // Up to 10 bonus points
				}
			} else {
				$score += 5; // Bonus for unlimited impressions
			}

			$scored_posts[] = array(
				'post'  => $candidate_post,
				'score' => $score,
			);
		}

		// Sort by score (highest first)
		usort(
			$scored_posts,
			function ( $a, $b ) {
				return $b['score'] <=> $a['score'];
			}
		);

		// Return the highest scoring post
		return ! empty( $scored_posts ) ? $scored_posts[0]['post'] : null;
	}

	/**
	Filter meta keys to only include those with 'uspw_' prefix

	@param array $meta Original meta array
	@return array Filtered meta array
	 */
	private function filter_meta_keys( $meta ) {
		$filtered_meta = array();
		foreach ( $meta as $key => $value ) {
			if ( strpos( $key, 'uspw_' ) === 0 ) {
				$filtered_meta[ $key ] = $value;
			}
		}
		return $filtered_meta;
	}

	/**
	 * Get user IP address
	 */
	private function get_user_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} else {
			return sanitize_text_field( isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '' );
		}
	}

	/**
	 * Determine if a template should be displayed
	 * All Server side logic for displaying the spin wheel
	 */
	private function should_display_conditions( $display_on, $not_display_on, $display_special, $not_display_special, $display_custom, $not_display_custom, $display_roles ) {

		global $post;

		$should_display = false;
		$is_logged_in   = is_user_logged_in();

		// ✅ Check Display Conditions
		if ( in_array( 'entire_site', $display_on ) ) {
			$should_display = true;
		}

		if ( is_page() && in_array( 'all_pages', $display_on ) ) {
			$should_display = true;
		}

		if ( is_single() && in_array( 'all_posts', $display_on ) ) {
			$should_display = true;
		}

		// Special Pages
		if ( is_front_page() && in_array( 'front_page', $display_special ) ) {
			$should_display = true;
		}

		if ( is_home() && in_array( 'blog_page', $display_special ) ) {
			$should_display = true;
		}

		if ( is_archive() && in_array( 'archive_page', $display_special ) ) {
			$should_display = true;
		}

		if ( is_404() && in_array( '404_page', $display_special ) ) {
			$should_display = true;
		}

		// Custom Selected Pages
		if ( $post && in_array( 'custom_pages', $display_on ) && in_array( $post->ID, $display_custom ) ) {
			$should_display = true;
		}

		// 🚀 IMPROVED: User Authentication Logic
		$has_logged_in_rule  = in_array( 'logged_in', $display_roles );
		$has_logged_out_rule = in_array( 'logged_out', $display_roles );

		// Handle logged out users
		if ( ! $is_logged_in ) {
			if ( $has_logged_in_rule && ! $has_logged_out_rule ) {
				$should_display = false; // Only logged-in allowed, user is logged out
			} elseif ( $has_logged_out_rule ) {
				$should_display = true; // Logged-out users explicitly allowed
			}
		}

		// Handle logged in users
		if ( $is_logged_in ) {
			if ( $has_logged_out_rule && ! $has_logged_in_rule ) {
				$should_display = false; // Only logged-out allowed, user is logged in
			} elseif ( $has_logged_in_rule ) {
				// Check if user has required role
				$user       = wp_get_current_user();
				$user_roles = array_diff( $display_roles, array( 'logged_in', 'logged_out' ) ); // Remove auth states

				if ( empty( $user_roles ) || ! empty( array_intersect( $user->roles, $user_roles ) ) ) {
					$should_display = true; // User has required role or no specific roles required
				} else {
					$should_display = false; // User doesn't have required role
				}
			}
		}

		// ❌ Check Not Display Conditions (Overrides Above)
		if ( in_array( 'entire_site', $not_display_on ) ) {
			return false;
		}

		if ( is_page() && in_array( 'all_pages', $not_display_on ) ) {
			return false;
		}

		if ( is_single() && in_array( 'all_posts', $not_display_on ) ) {
			return false;
		}

		if ( is_front_page() && in_array( 'front_page', $not_display_special ) ) {
			return false;
		}

		if ( is_home() && in_array( 'blog_page', $not_display_special ) ) {
			return false;
		}

		if ( is_archive() && in_array( 'archive_page', $not_display_special ) ) {
			return false;
		}

		if ( is_404() && in_array( '404_page', $not_display_special ) ) {
			return false;
		}

		if ( $post && in_array( 'custom_pages', $not_display_on ) && in_array( $post->ID, $not_display_custom ) ) {
			return false;
		}

		// 🚀 IMPROVED: Role-based exclusion logic
		if ( $is_logged_in ) {
			$user           = wp_get_current_user();
			$excluded_roles = array_diff( $not_display_roles ?? array(), array( 'logged_in', 'logged_out' ) );

			if ( ! empty( array_intersect( $user->roles, $excluded_roles ) ) ) {
				return false; // User has excluded role
			}
		}

		return $should_display;
	}

	/**
	 * Get Coupons without Coupon Code
	 */
	public function get_coupons_display( $coupons ) {
		if ( empty( $coupons ) ) {
			return array();
		}

		// If $coupons is an array with a single JSON string, decode it
		if ( is_array( $coupons ) && count( $coupons ) === 1 && is_string( $coupons[0] ) ) {
			$coupons = json_decode( $coupons[0], true );
		} elseif ( is_string( $coupons ) ) {
			$coupons = json_decode( $coupons, true );
		}

		if ( ! is_array( $coupons ) ) {
			return array();
		}

		$result = array();
		foreach ( $coupons as $coupon ) {
			if ( isset( $coupon['label'] ) && isset( $coupon['color'] ) ) {
				$probability = isset( $coupon['probability'] ) ? floatval( $coupon['probability'] ) : 1;
				$_coupon_code = isset( $coupon['code'] ) ? $coupon['code'] : '';
				$coupon['code'] = ( $probability <= 1 ) ? 'NO_CODE' : $_coupon_code;

				$coupon_data = array(
					'label'       => $coupon['label'],
					'color'       => $coupon['color'],
					'code'        => isset( $coupon['code'] ) ? $this->encrypt_coupon( $coupon['code'] ) : '', // Encrypt coupon code
					'probability' => $probability,
				);

				// Add lost fields if they exist
				if ( isset( $coupon['lost'] ) && is_array( $coupon['lost'] ) ) {
					$coupon_data['lost'] = array(
						'label' => $coupon['lost']['label'] ?? '',
						'color' => $coupon['lost']['color'] ?? '',
					);
				} else {
					$coupon_data['lost'] = array(
						'label' => 'Better luck next time!',
						'color' => '#277162', // Default lost color
					);
				}

				$result[] = $coupon_data;
			}
		}

		return $result;
	}

	/**
	 * Simple Encription Coupon
	 */
	public function encrypt_coupon( $coupon_code ) {
		if ( ! $coupon_code ) {
			return '';
		}
		$salt = 'wowDevsSecret';
		return base64_encode( $coupon_code . '|' . $salt );
	}

	/**
	 * Handle impression count for the spin wheel
	 */
	public function impression_count() {
		// todo: Implement the logic to handle impression count
	}


	/**
	 * Enqueue scripts and styles for the spin wheel
	 */
	public function enqueue_scripts() {
		wp_register_script( 'wow-modal', USPIN_WHEEL_ASSETS_URL . 'vendor/js/jquery.wow-modal.min.js', array( 'jquery' ), '1.0.0', true );
		wp_register_style( 'wow-modal', USPIN_WHEEL_ASSETS_URL . 'vendor/css/wow-modal.min.css', array(), '1.0.0' );
			wp_enqueue_script( 'wow-modal' );
		wp_enqueue_style( 'wow-modal' );

		wp_enqueue_style( 'spin-wheel-style', USPIN_WHEEL_ASSETS_URL . 'css/modules/spin-wheel.css', array(), '1.0.0' );
		// todo: jquery for Blocks Themes
		wp_register_script( 'spin-wheel', USPIN_WHEEL_ASSETS_URL . 'js/spin-wheel.js', array( 'jquery', 'wow-modal' ), '1.0.0', true );

		wp_enqueue_script( 'spin-wheel' );

		// Localize script with data
		wp_localize_script(
			'spin-wheel',
			'USPIN_WHEEL_UI_CONFIG',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'ultimate_spin_wheel' ),
				'settings' => $this->settings,
			)
		);
	}
	/**
	 * Render the spin wheel HTML
	 */
	public function render_spin_wheel() {
		// Check if the spin wheel should be displayed
		if ( ! $this->spin_wheel_init() ) {
			return;
		}

		// Get the current spin wheel post
		$spin_wheel_post = $this->get_current_spin_wheel_post();

		if ( ! $spin_wheel_post ) {
			return;
		}

		/**
		 * Get user name and email
		 */
		$user_name  = is_user_logged_in() ? wp_get_current_user()->display_name : '';
		$user_email = is_user_logged_in() ? wp_get_current_user()->user_email : '';

		/**
		 * Get custom designs with fallbacks to defaults
		 */
		$custom_designs = $this->settings['custom_designs'] ?? array();

		// Generate custom CSS
		$custom_css = $this->generate_custom_css( $custom_designs, $spin_wheel_post->post_id );

		// Default values with fallbacks
		$spin_button_text  = $custom_designs['spinButton']['text'] ?? 'Go!';
		$form_title_text   = $custom_designs['formTitle']['text'] ?? 'Let\'s try luck!';
		$form_submit_text  = $custom_designs['formSubmitButton']['text'] ?? 'Spin the Wheel';
		$name_placeholder  = $custom_designs['formInputs']['namePlaceholder'] ?? 'Enter your name';
		$email_placeholder = $custom_designs['formInputs']['emailPlaceholder'] ?? 'Enter your email';
		$privacy_text      = $custom_designs['privacyText']['text'] ?? 'Privacy & Policy';
		$privacy_url       = $custom_designs['privacyText']['url'] ?? 'javascript:void(0);';
		$prize_won_title   = $custom_designs['prizeWonTitle']['text'] ?? 'Congratulations!';
		$prize_won_msg     = $custom_designs['prizeWonMsg']['text'] ?? 'You won a {{discount_label}} discount!';
		$coupon_win_text   = $custom_designs['couponButton']['winText'] ?? 'Start shopping!';
		$wheel_lost_text   = $custom_designs['spinButton']['lostText'] ?? 'Go again?';

		$coupons = $this->get_coupons_display( $spin_wheel_post->meta['uspw_coupons'] ?? array() );

		?>
		<style>
		<?php echo $custom_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</style>
	<div class="ultimate-spin-wheel spinWheel" data-sm-init="true" id="spin-wheel-<?php echo esc_attr( $spin_wheel_post->post_id ); ?>">
			<div class="wheelWrap">
				<div class="wheel" data-spin-circles="6" data-spin-speed="8s">
					<?php foreach ( $coupons as $coupon ) : ?>
						<div class="area" 
							data-wheel-bg="<?php echo esc_attr( $coupon['color'] ); ?>" 
							data-wheel-prize="wins"
				data-coupon-code="<?php echo esc_attr( $coupon['code'] ); ?>" 
				data-probability="<?php echo esc_attr( $coupon['probability'] ?? '1' ); ?>"
							data-wheel-message="<?php echo esc_attr( $coupon['label'] ); ?>">
							<span><?php echo esc_html( $coupon['label'] ); ?></span>
						</div>
						<div class="area" 
							data-wheel-bg="<?php echo esc_attr( $coupon['lost']['color'] ); ?>" 
							data-wheel-prize="lost"
							data-wheel-message="<?php echo esc_attr( isset( $coupon['lost']['label'] ) && $coupon['lost']['label'] ? $coupon['lost']['label'] : 'Better luck next time!' ); ?>">
							<span></span>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="msg">
					<div class="title"><?php echo esc_html( $prize_won_title ); ?></div>
					<div class="prizeMsg"><?php echo esc_html( $prize_won_msg ); ?></div>
					<div class="sc-btn sc-coupon">
			<span class="sc-coupon-code">DUMMY_CODE</span>
			<?php $this->svg_copy_icon(); ?>
			</div>
					<a class="sc-small" target="_blank" href="<?php echo esc_url( $privacy_url ); ?>"><?php echo esc_html( $privacy_text ); ?></a>
				</div>
				<div class="sc-form-wrap">
					<div class="title"><?php echo esc_html( $form_title_text ); ?></div>
					<form class="sc-spin-form" method="post">
						<input type="text" name="name" placeholder="<?php echo esc_attr( $name_placeholder ); ?>" value="<?php echo esc_attr( $user_name ); ?>">
						<input type="email" name="email" placeholder="<?php echo esc_attr( $email_placeholder ); ?>" value="<?php echo esc_attr( $user_email ); ?>" required>
						<button type="submit" class="spin"><?php echo esc_html( $form_submit_text ); ?></button>
					</form>
					<a class="sc-small" target="_blank" href="<?php echo esc_url( $privacy_url ); ?>"><?php echo esc_html( $privacy_text ); ?></a>
				</div>
				<div class="start" data-wheel-lost-text="<?php echo esc_attr( $wheel_lost_text ); ?>"><?php echo esc_html( $spin_button_text ); ?></div>
				<div class="marker"></div>
			</div>
		</div>
		<?php
	}

	public function svg_copy_icon() {
		?>
		<svg viewBox="0 0 256 256" xmlns="http://www.w3.org/2000/svg">
		<path d="M48.186 92.137c0-8.392 6.49-14.89 16.264-14.89s29.827-.225 29.827-.225-.306-6.99-.306-15.88c0-8.888 7.954-14.96 17.49-14.96 9.538 0 56.786.401 61.422.401 4.636 0 8.397 1.719 13.594 5.67 5.196 3.953 13.052 10.56 16.942 14.962 3.89 4.402 5.532 6.972 5.532 10.604 0 3.633 0 76.856-.06 85.34-.059 8.485-7.877 14.757-17.134 14.881-9.257.124-29.135.124-29.135.124s.466 6.275.466 15.15-8.106 15.811-17.317 16.056c-9.21.245-71.944-.49-80.884-.245-8.94.245-16.975-6.794-16.975-15.422s.274-93.175.274-101.566zm16.734 3.946l-1.152 92.853a3.96 3.96 0 0 0 3.958 4.012l73.913.22a3.865 3.865 0 0 0 3.91-3.978l-.218-8.892a1.988 1.988 0 0 0-2.046-1.953s-21.866.64-31.767.293c-9.902-.348-16.672-6.807-16.675-15.516-.003-8.709.003-69.142.003-69.142a1.989 1.989 0 0 0-2.007-1.993l-23.871.082a4.077 4.077 0 0 0-4.048 4.014zm106.508-35.258c-1.666-1.45-3.016-.84-3.016 1.372v17.255c0 1.106.894 2.007 1.997 2.013l20.868.101c2.204.011 2.641-1.156.976-2.606l-20.825-18.135zm-57.606.847a2.002 2.002 0 0 0-2.02 1.988l-.626 96.291a2.968 2.968 0 0 0 2.978 2.997l75.2-.186a2.054 2.054 0 0 0 2.044-2.012l1.268-62.421a1.951 1.951 0 0 0-1.96-2.004s-26.172.042-30.783.042c-4.611 0-7.535-2.222-7.535-6.482S152.3 63.92 152.3 63.92a2.033 2.033 0 0 0-2.015-2.018l-36.464-.23z" stroke="currentColor" fill-rule="evenodd"/>
	</svg>
		<?php
	}

	/**
	 * Generate custom CSS for the spin wheel based on custom designs
	 */
	public function generate_custom_css( $custom_designs, $post_id ) {
		$id = 'spin-wheel-' . $post_id;

		$css_variables = array();

		// Only add variables if they exist in custom_designs
		if ( isset( $custom_designs['viewPanel']['backgroundColor'] ) ) {
			$css_variables[] = sprintf( '--panel-bg-color: %s;', esc_attr( $custom_designs['viewPanel']['backgroundColor'] ) );
		}

		if ( isset( $custom_designs['spinButton']['color'] ) ) {
			$css_variables[] = sprintf( '--spin-btn-color: %s;', esc_attr( $custom_designs['spinButton']['color'] ) );
		}

		if ( isset( $custom_designs['spinButton']['backgroundColor'] ) ) {
			$css_variables[] = sprintf( '--spin-btn-bg: %s;', esc_attr( $custom_designs['spinButton']['backgroundColor'] ) );
		}

		if ( isset( $custom_designs['spinButton']['hoverColor'] ) ) {
			$css_variables[] = sprintf( '--spin-btn-hover: %s;', esc_attr( $custom_designs['spinButton']['hoverColor'] ) );
		}

		if ( isset( $custom_designs['spinButton']['fontSize'] ) ) {
			$css_variables[] = sprintf( '--spin-btn-font-size: %s;', esc_attr( $custom_designs['spinButton']['fontSize'] ) );
		}

		if ( isset( $custom_designs['formTitle']['color'] ) ) {
			$css_variables[] = sprintf( '--form-title-color: %s;', esc_attr( $custom_designs['formTitle']['color'] ) );
		}

		if ( isset( $custom_designs['formTitle']['fontSize'] ) ) {
			$css_variables[] = sprintf( '--form-title-size: %s;', esc_attr( $custom_designs['formTitle']['fontSize'] ) );
		}

		if ( isset( $custom_designs['formTitle']['fontWeight'] ) ) {
			$css_variables[] = sprintf( '--form-title-weight: %s;', esc_attr( $custom_designs['formTitle']['fontWeight'] ) );
		}

		if ( isset( $custom_designs['formSubmitButton']['color'] ) ) {
			$css_variables[] = sprintf( '--form-submit-color: %s;', esc_attr( $custom_designs['formSubmitButton']['color'] ) );
		}

		if ( isset( $custom_designs['formSubmitButton']['backgroundColor'] ) ) {
			$css_variables[] = sprintf( '--form-submit-bg: %s;', esc_attr( $custom_designs['formSubmitButton']['backgroundColor'] ) );
		}

		if ( isset( $custom_designs['formSubmitButton']['hoverColor'] ) ) {
			$css_variables[] = sprintf( '--form-submit-hover: %s;', esc_attr( $custom_designs['formSubmitButton']['hoverColor'] ) );
		}

		if ( isset( $custom_designs['formSubmitButton']['fontSize'] ) ) {
			$css_variables[] = sprintf( '--form-submit-size: %s;', esc_attr( $custom_designs['formSubmitButton']['fontSize'] ) );
		}

		if ( isset( $custom_designs['formSubmitButton']['borderRadius'] ) ) {
			$css_variables[] = sprintf( '--form-submit-radius: %dpx;', intval( $custom_designs['formSubmitButton']['borderRadius'] ) );
		}

		if ( isset( $custom_designs['formInputs']['borderColor'] ) ) {
			$css_variables[] = sprintf( '--input-border: %s;', esc_attr( $custom_designs['formInputs']['borderColor'] ) );
		}

		if ( isset( $custom_designs['formInputs']['focusColor'] ) ) {
			$css_variables[] = sprintf( '--input-focus: %s;', esc_attr( $custom_designs['formInputs']['focusColor'] ) );
		}

		if ( isset( $custom_designs['privacyText']['color'] ) ) {
			$css_variables[] = sprintf( '--privacy-color: %s;', esc_attr( $custom_designs['privacyText']['color'] ) );
		}

		if ( isset( $custom_designs['privacyText']['fontSize'] ) ) {
			$css_variables[] = sprintf( '--privacy-size: %s;', esc_attr( $custom_designs['privacyText']['fontSize'] ) );
		}

		if ( isset( $custom_designs['prizeWonTitle']['color'] ) ) {
			$css_variables[] = sprintf( '--prize-title-color: %s;', esc_attr( $custom_designs['prizeWonTitle']['color'] ) );
		}

		if ( isset( $custom_designs['prizeWonTitle']['fontSize'] ) ) {
			$css_variables[] = sprintf( '--prize-title-size: %s;', esc_attr( $custom_designs['prizeWonTitle']['fontSize'] ) );
		}

		if ( isset( $custom_designs['prizeWonMsg']['color'] ) ) {
			$css_variables[] = sprintf( '--prize-msg-color: %s;', esc_attr( $custom_designs['prizeWonMsg']['color'] ) );
		}

		if ( isset( $custom_designs['prizeWonMsg']['fontSize'] ) ) {
			$css_variables[] = sprintf( '--prize-msg-size: %s;', esc_attr( $custom_designs['prizeWonMsg']['fontSize'] ) );
		}

		if ( isset( $custom_designs['prizeLostTitle']['color'] ) ) {
			$css_variables[] = sprintf( '--lost-title-color: %s;', esc_attr( $custom_designs['prizeLostTitle']['color'] ) );
		}

		if ( isset( $custom_designs['prizeLostTitle']['fontSize'] ) ) {
			$css_variables[] = sprintf( '--lost-title-size: %s;', esc_attr( $custom_designs['prizeLostTitle']['fontSize'] ) );
		}

		if ( isset( $custom_designs['couponButton']['color'] ) ) {
			$css_variables[] = sprintf( '--coupon-btn-color: %s;', esc_attr( $custom_designs['couponButton']['color'] ) );
		}

		if ( isset( $custom_designs['couponButton']['backgroundColor'] ) ) {
			$css_variables[] = sprintf( '--coupon-btn-bg: %s;', esc_attr( $custom_designs['couponButton']['backgroundColor'] ) );
		}

		if ( isset( $custom_designs['couponButton']['hoverColor'] ) ) {
			$css_variables[] = sprintf( '--coupon-btn-hover: %s;', esc_attr( $custom_designs['couponButton']['hoverColor'] ) );
		}

		if ( isset( $custom_designs['couponButton']['borderRadius'] ) ) {
			$css_variables[] = sprintf( '--coupon-btn-radius: %dpx;', intval( $custom_designs['couponButton']['borderRadius'] ) );
		}

		if ( isset( $custom_designs['wheel']['lostColor'] ) ) {
			$css_variables[] = sprintf( '--wheel-lost-color: %s;', esc_attr( $custom_designs['wheel']['lostColor'] ) );
		}

		if ( isset( $custom_designs['wheel']['borderColor'] ) ) {
			$css_variables[] = sprintf( '--wheel-border-color: %s;', esc_attr( $custom_designs['wheel']['borderColor'] ) );
		}

		if ( isset( $custom_designs['wheel']['borderWidth'] ) ) {
			$css_variables[] = sprintf( '--wheel-border-width: %dpx;', intval( $custom_designs['wheel']['borderWidth'] ) );
		}

		if ( empty( $css_variables ) ) {
			return '';
		}

		$css = sprintf(
			'/* Spin Wheel Custom Styles */
      #%s {
        %s
      }',
			esc_attr( $id ),
			implode( "\n  ", $css_variables )
		);

		return $css;
	}
}
