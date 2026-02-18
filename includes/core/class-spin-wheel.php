<?php

namespace USPIN_WHEEL\Includes\Core;

defined( 'ABSPATH' ) || exit;

class Spin_Wheel {



	private static $instance = null;
	protected $post_type     = 'wowdevs_engage';
	private $settings        = null;
	private $defaults        = null;
	private $global_settings = null; // Cached global settings to avoid multiple get_option calls

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'wp_ajax_ultimate_spin_wheel_sc_imp_count', [ $this, 'impression_count' ] );
		add_action( 'wp_ajax_nopriv_ultimate_spin_wheel_sc_imp_count', [ $this, 'impression_count' ] );

		add_action( 'wp_ajax_ultimate_spin_wheel_spinned', [ $this, 'spin_wheel_spinned' ] );
		add_action( 'wp_ajax_nopriv_ultimate_spin_wheel_spinned', [ $this, 'spin_wheel_spinned' ] );

		add_action( 'wp_ajax_ultimate_spin_wheel_check_identity', [ $this, 'spin_wheel_check_identity' ] );
		add_action( 'wp_ajax_nopriv_ultimate_spin_wheel_check_identity', [ $this, 'spin_wheel_check_identity' ] );

		add_action( 'wp_ajax_ultimate_spin_wheel_process_spin', [ $this, 'spin_wheel_process_spin' ] );
		add_action( 'wp_ajax_nopriv_ultimate_spin_wheel_process_spin', [ $this, 'spin_wheel_process_spin' ] );

		add_action( 'wp_loaded', [ $this, 'auto_apply_coupon' ], 20 );
		add_action(
			'wp',
			function () {
				$spin_wheel = $this->spin_wheel_init();
				if ( $spin_wheel ) {
					add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
					add_action( 'wp_head', [ $this, 'inject_dynamic_styles' ] );
					add_action( 'wp_footer', [ $this, 'render_spin_wheel' ] );
				}
			}
		);
	}

	public function auto_apply_coupon() {
		if ( is_admin() || ! function_exists( 'WC' ) ) {
			return;
		}

		$coupon_code = '';

		// 1. Capture code from multiple possible sources (Unique param > Standard param > Cookie)
		// Nonce verification is recommended even for GET requests if they perform actions, but applying a coupon
		// based on a URL parameter is generally considered safe if properly sanitized.
		// However, adhering to strict standards, we might want to check a nonce if available, but for public-facing
		// coupon links, it's often not possible. We'll proceed with sanitization.

		if ( isset( $_GET['uspw_coupon'] ) ) {
			$coupon_code = sanitize_text_field( wp_unslash( $_GET['uspw_coupon'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( isset( $_GET['coupon'] ) ) {
			$coupon_code = sanitize_text_field( wp_unslash( $_GET['coupon'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( isset( $_COOKIE['uspw_apply_coupon'] ) ) {
			$coupon_code = sanitize_text_field( wp_unslash( $_COOKIE['uspw_apply_coupon'] ) );
		}

		if ( empty( $coupon_code ) ) {
			return;
		}

		// 2. Cleanup reward cookie if it exists
		if ( isset( $_COOKIE['uspw_apply_coupon'] ) ) {
			setcookie( 'uspw_apply_coupon', '', time() - 3600, '/' );
		}

		// 3. Application to WooCommerce
		if ( WC()->cart ) {
			// Ensure a session is started (crucial for guest reward persistence)
			if ( WC()->session && ! WC()->session->has_session() ) {
				WC()->session->set_customer_session_cookie( true );
			}

			if ( ! WC()->cart->has_discount( $coupon_code ) ) {
				WC()->cart->apply_coupon( $coupon_code );
				WC()->cart->calculate_totals(); // Force update to reflect changes immediately
			}
		}
	}

	/**
	 * AJAX Handler to check if user has already played based on email/phone
	 */
	public function spin_wheel_check_identity() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimate_spin_wheel' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'ultimate-spin-wheel' ) ] );
		}

		$campaign_id = isset( $_POST['campaign_id'] ) ? intval( wp_unslash( $_POST['campaign_id'] ) ) : 0;
		$email       = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$phone       = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';

		if ( ! $campaign_id ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Campaign ID is required', 'ultimate-spin-wheel' ) ] );
		}

		// Use centralized defaults
		$form_settings = $this->get_merged_meta( $campaign_id, 'uspw_form_settings', 'formSettings' );

		// Read identity check type from form_settings
		$restrict_settings = $form_settings['restrictSpinPerUser'] ?? [];
		$check_type        = $restrict_settings['identityCheckType'] ?? 'email';
		$cooldown_type     = $restrict_settings['cooldownType'] ?? 'never';
		$cooldown_value    = intval( $restrict_settings['cooldownValue'] ?? 24 );

		// Get custom "already played" message from form_settings
		$custom_message = $restrict_settings['alreadyPlayedMessage'] ?? '';
		if ( empty( $custom_message ) ) {
			$custom_message = esc_html__( 'You have already played this campaign!', 'ultimate-spin-wheel' );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'wdengage_entries';

		$where_conditions = [];
		$where_values     = [];

		$where_conditions[] = 'campaign_id = %d';
		$where_values[]     = $campaign_id;

		$identity_conditions = [];

		if ( ( 'email' === $check_type || 'both' === $check_type ) && ! empty( $email ) ) {
			$identity_conditions[] = 'email = %s';
			$where_values[]        = $email;
		}

		if ( ( 'phone' === $check_type || 'both' === $check_type ) && ! empty( $phone ) ) {
			$identity_conditions[] = 'phone = %s';
			$where_values[]        = $phone;
		}

		if ( empty( $identity_conditions ) ) {
			wp_send_json_success( [ 'already_played' => false ] );
		}

		// Use OR between identity fields if check_type is 'both', or just the single field
		$where_conditions[] = '(' . implode( ' OR ', $identity_conditions ) . ')';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$query = $wpdb->prepare(
			"SELECT created_at FROM $table_name WHERE " . implode( ' AND ', $where_conditions ) . " ORDER BY created_at DESC LIMIT 1",
			$where_values
		); 

		$last_entry_time = $wpdb->get_var( $query ); 
		// phpcs:enable

		if ( $last_entry_time ) {
			// Entry exists - check cooldown
			if ( 'never' === $cooldown_type ) {
				// Blocked forever (one-time campaign)
				wp_send_json_success(
					[
						'already_played'    => true,
						'cooldown_type'     => 'never',
						'message'           => $custom_message,
					]
				);
			}

		// Calculate cooldown end time
			$cooldown_seconds = ( 'hours' === $cooldown_type )
				? $cooldown_value * 3600
				: $cooldown_value * 86400;

			$last_play_ts    = strtotime( $last_entry_time );
			$cooldown_ends_ts = $last_play_ts + $cooldown_seconds;
			$current_time     = time();

			if ( $current_time < $cooldown_ends_ts ) {
				// Still in cooldown - return remaining time
				$remaining_seconds = $cooldown_ends_ts - $current_time;

				wp_send_json_success(
					[
						'already_played'     => true,
						'cooldown_type'      => $cooldown_type,
						'cooldown_ends_at'   => $cooldown_ends_ts,
						'remaining_seconds'  => $remaining_seconds,
						'message'            => $custom_message,
					]
				);
			}

			// Cooldown has expired - user can play again
		}

		wp_send_json_success( [ 'already_played' => false ] );
	}


	public function spin_wheel_spinned() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimate_spin_wheel' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'ultimate-spin-wheel' ) ] );
		}

		$name           = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : null;
		$email          = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : null;
		$campaign_id    = isset( $_POST['campaign_id'] ) ? intval( wp_unslash( $_POST['campaign_id'] ) ) : 0;
		$campaign_title = isset( $_POST['campaign_title'] ) ? sanitize_text_field( wp_unslash( $_POST['campaign_title'] ) ) : null;
		$coupon_code    = isset( $_POST['coupon_code'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) ) : '';
		$coupon_title   = isset( $_POST['coupon_title'] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_title'] ) ) : '';
		$status         = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : null;
		$phone          = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$segment_index  = isset( $_POST['segment_index'] ) ? intval( wp_unslash( $_POST['segment_index'] ) ) : -1;

		// SECURE COUPON FETCHING
		if ( $segment_index >= 0 && 'won' === $status ) {
			$coupons_meta = get_post_meta( $campaign_id, 'uspw_coupons', true );
			if ( is_string( $coupons_meta ) ) {
				$coupons_array = json_decode( $coupons_meta, true );
			} else {
				$coupons_array = $coupons_meta;
			}

			if ( is_array( $coupons_array ) && isset( $coupons_array[ $segment_index ] ) ) {
				$won_coupon = $coupons_array[ $segment_index ];
				$_raw_code  = trim( $won_coupon['code'] ?? '' );

				// Check if it's a unique coupon pool (Explicitly marked as unique)
				if ( ! empty( $won_coupon['is_unique'] ) ) {
					$codes = array_filter( array_map( 'trim', explode( ',', $_raw_code ) ) );
					if ( ! empty( $codes ) ) {
						$coupon_code = array_shift( $codes );
						// Update the meta with remaining codes
						$new_code_val                            = implode( ', ', $codes );
						$coupons_array[ $segment_index ]['code'] = $new_code_val;
						update_post_meta( $campaign_id, 'uspw_coupons', wp_json_encode( $coupons_array ) );
					}
				} else {
					// Static code
					$coupon_code = $_raw_code;
				}
			}
		}

		$defaults = [
			'name'          => '',
			'email'         => '',
			'campaign_type' => 'Spin Wheel',
		];

		$args = [
			'campaign_id'    => $campaign_id,
			'campaign_title' => $campaign_title,
			'name'           => $name,
			'email'          => $email,
			'phone'          => $phone,
			'others_data' => json_encode(
				[
					'coupon_title' => $coupon_title,
					'coupon_code'  => $coupon_code,
					'status'       => $status,
					'phone'        => $phone,
				]
			),
			'user_data' => json_encode(
				[
					'ip_address' => $this->get_user_ip(),
					'device_id'  => $this->get_device_id(),
					'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
				]
			),
			'created_at'     => current_time( 'mysql', 1 ),
		];

		$data = wp_parse_args( $args, $defaults );

		global $wpdb;
		$table_name = $wpdb->prefix . 'wdengage_entries';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table requires direct database query.
		$wpdb->insert( $table_name, $data );
		$entry_id = $wpdb->insert_id;

		if ( $wpdb->last_error ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Error saving data', 'ultimate-spin-wheel' ) ] );
		} else {
			// Trigger email if feature is enabled
			$send_on_email = get_post_meta( $campaign_id, 'uspw_send_coupon_on_email', true );
			if ( 'yes' === $send_on_email && ! empty( $email ) && 'won' === $status && ! empty( $coupon_code ) ) {
				/* translators: %s: Site name */
				$subject = sprintf( esc_html__( 'Your Coupon from %s', 'ultimate-spin-wheel' ), get_bloginfo( 'name' ) );
				$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
				$message = $this->get_email_template(
					[
						'site_name'    => get_bloginfo( 'name' ),
						'site_url'     => home_url(),
						'coupon_title' => $coupon_title,
						'coupon_code'  => $coupon_code,
					]
				);
				wp_mail( $email, $subject, $message, $headers );
			}

			/**
			 * Hook after a coupon is won.
			 * Allows developers to implement custom notifications (e.g. SMS).
			 *
			 * @param int   $campaign_id    The campaign ID.
			 * @param array $data           The entry data.
			 */
			if ( apply_filters( 'ultimate_spin_wheel_pro_init', false ) ) {
				do_action( 'uspw_after_coupon_won', $campaign_id, $data );
			}

			// Trigger Integrations Queue
			if ( class_exists( '\USPIN_WHEEL\Includes\Core\Integrations' ) ) {
				\USPIN_WHEEL\Includes\Core\Integrations::instance()->enqueue_lead( $campaign_id, $data, $entry_id );
			}

			wp_send_json_success([
				'message'     => esc_html__( 'Data saved successfully', 'ultimate-spin-wheel' ),
				'coupon_code' => $coupon_code,
			]);
		}
	}

	/**
	 * Process Spin (Server-Side Winner Selection)
	 */
	public function spin_wheel_process_spin() {
		/**
		 * Check if user is blocked
		 */
		if ( $this->is_blocked() ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Your access has been restricted.', 'ultimate-spin-wheel' ) ] );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimate_spin_wheel' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'ultimate-spin-wheel' ) ] );
		}

		$campaign_id = isset( $_POST['campaign_id'] ) ? intval( wp_unslash( $_POST['campaign_id'] ) ) : 0;
		if ( ! $campaign_id ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid campaign ID', 'ultimate-spin-wheel' ) ] );
		}

		// Verify campaign exists and is published
		$campaign_post = get_post( $campaign_id );
		if ( ! $campaign_post || $campaign_post->post_type !== 'wowdevs_engage' || $campaign_post->post_status !== 'publish' ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Campaign not found or not available.', 'ultimate-spin-wheel' ) ] );
		}

		/**
		 * Check for disposable/temporary email addresses
		 */
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		if ( ! empty( $email ) && $this->is_disposable_email( $email ) ) {
			// Get custom message from settings
			$settings       = $this->get_global_settings();
			$custom_message = $settings['security']['disposable_email_block']['message'] ?? '';
			$message        = ! empty( $custom_message ) ? $custom_message : esc_html__( 'Please use a valid, non-temporary email address.', 'ultimate-spin-wheel' );

			wp_send_json_error([
				'disposable_email' => true,
				'message'          => $message,
			]);
		}

		/**
	 * Server-side Form Field Validation
	 * Validate required fields based on form settings to prevent HTML manipulation
	 */
	$form_settings = $this->get_merged_meta( $campaign_id, 'uspw_form_settings', 'formSettings' );
	
	// Get raw meta to check if settings exist
	$meta_exists = get_post_meta( $campaign_id, 'uspw_form_settings', true );
	
	// If no meta exists, default: email and name are required
	if ( empty( $meta_exists ) ) {
		// Validate Email (required by default)
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		if ( empty( $email ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Email is required.', 'ultimate-spin-wheel' ) ] );
		}
		// Validate email format
		if ( ! is_email( $email ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Please enter a valid email address.', 'ultimate-spin-wheel' ) ] );
		}
		
		// Validate Name (required by default)
		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		if ( empty( $name ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Name is required.', 'ultimate-spin-wheel' ) ] );
		}
		// Validate name format (should contain at least some letters, not just numbers/special chars)
		if ( ! preg_match( '/[a-zA-Z]/', $name ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Please enter a valid name.', 'ultimate-spin-wheel' ) ] );
		}
	} else {
		// Meta exists, use the settings structure: inputEmail, inputName, inputPhone
		
		// Validate Email Field
		$input_email = $form_settings['inputEmail'] ?? [];
		if ( ! empty( $input_email['enable'] ) && ! empty( $input_email['required'] ) ) {
			$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
			if ( empty( $email ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Email is required.', 'ultimate-spin-wheel' ) ] );
			}
			// Validate email format
			if ( ! is_email( $email ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Please enter a valid email address.', 'ultimate-spin-wheel' ) ] );
			}
		}
		
		// Validate Phone Field
		$input_phone = $form_settings['inputPhone'] ?? [];
		if ( ! empty( $input_phone['enable'] ) && ! empty( $input_phone['required'] ) ) {
			$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
			if ( empty( $phone ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Phone number is required.', 'ultimate-spin-wheel' ) ] );
			}
			// Validate phone format (allows +, -, spaces, parentheses, and digits)
			if ( ! preg_match( '/^[\d\s\-\+\(\)]+$/', $phone ) || strlen( preg_replace( '/\D/', '', $phone ) ) < 7 ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Please enter a valid phone number.', 'ultimate-spin-wheel' ) ] );
			}
		}
		
		// Validate Name Field
		$input_name = $form_settings['inputName'] ?? [];
		if ( ! empty( $input_name['enable'] ) && ! empty( $input_name['required'] ) ) {
			$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			if ( empty( $name ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Name is required.', 'ultimate-spin-wheel' ) ] );
			}
			// Validate name format (should contain at least some letters, not just numbers/special chars)
			if ( ! preg_match( '/[a-zA-Z]/', $name ) ) {
				wp_send_json_error( [ 'message' => esc_html__( 'Please enter a valid name.', 'ultimate-spin-wheel' ) ] );
			}
		}
	}

	// Additional validation for fields even if not required but provided
	// This validates format even when fields are optional but user submits them
	if ( ! empty( $_POST['email'] ) ) {
		$email = sanitize_email( wp_unslash( $_POST['email'] ) );
		if ( ! is_email( $email ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Please enter a valid email address.', 'ultimate-spin-wheel' ) ] );
		}
	}

	if ( ! empty( $_POST['phone'] ) ) {
		$phone = sanitize_text_field( wp_unslash( $_POST['phone'] ) );
		if ( ! preg_match( '/^[\d\s\-\+\(\)]+$/', $phone ) || strlen( preg_replace( '/\D/', '', $phone ) ) < 7 ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Please enter a valid phone number.', 'ultimate-spin-wheel' ) ] );
		}
	}

	if ( ! empty( $_POST['name'] ) ) {
		$name = sanitize_text_field( wp_unslash( $_POST['name'] ) );
		if ( ! preg_match( '/[a-zA-Z]/', $name ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Please enter a valid name.', 'ultimate-spin-wheel' ) ] );
		}
	}

	/**
	 * Hook: uspw_before_spin_validation
	 * 
	 * Allow developers to add custom validation logic before spin processing.
	 * Returning false will block the spin.
	 * 
	 * @param bool  $can_spin    Whether the user can spin (default: true)
	 * @param int   $campaign_id Campaign ID
	 * @param array $user_data   User submitted data (email, phone, name)
	 * 
	 * @since 1.0.0
	 */
	$user_data = [
		'email' => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
		'phone' => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
		'name'  => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
	];
	$can_spin = true;
	if ( apply_filters( 'ultimate_spin_wheel_pro_init', false ) ) {
		$can_spin = apply_filters( 'uspw_before_spin_validation', $can_spin, $campaign_id, $user_data );
	}

	if ( ! $can_spin ) {
		wp_send_json_error( [ 'message' => esc_html__( 'You are not eligible to spin at this time.', 'ultimate-spin-wheel' ) ] );
	}


		// Load Coupons
		$coupons_meta = get_post_meta( $campaign_id, 'uspw_coupons', true );
		$prizes       = [];

		if ( is_array( $coupons_meta ) ) {
			$prizes = $coupons_meta;
		} elseif ( is_string( $coupons_meta ) ) {
			// Handle legacy single-string JSON
			$decoded = json_decode( $coupons_meta, true );
			$prizes  = is_array( $decoded ) ? $decoded : [];
			// Fix nested array issue if present (legacy format)
			if ( count( $prizes ) === 1 && isset( $prizes[0] ) && is_string( $prizes[0] ) ) {
				$prizes = json_decode( $prizes[0], true ) ?: [];
			}
		}

		if ( empty( $prizes ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'No prizes configured', 'ultimate-spin-wheel' ) ] );
		}

		// Calculate total weight for probability
		$total_weight    = 0;
		$weighted_prizes = [];

		foreach ( $prizes as $index => $prize ) {
			// Check inventory / exhaustion for unique prizes
			if ( ! empty( $prize['is_unique'] ) ) {
				$_raw_code = trim( $prize['code'] ?? '' );
				// If code is empty or no valid codes after split, skip this prize (treat as 0 probability)
				if ( empty( $_raw_code ) ) {
					continue;
				}
				$codes = array_filter( array_map( 'trim', explode( ',', $_raw_code ) ) );
				if ( empty( $codes ) ) {
					continue;
				}
			}

			// PRO FEATURE: WooCommerce Dynamic Coupon
			if ( isset( $prize['coupon_type'] ) && 'wc_dynamic' === $prize['coupon_type'] ) {
				$is_pro = apply_filters( 'ultimate_spin_wheel_pro_init', false );
				if ( ! $is_pro ) {
					continue;
				}
			}

			$probability = isset( $prize['probability'] ) ? floatval( $prize['probability'] ) : 0;
			if ( $probability > 0 ) {
				$total_weight     += $probability;
				$weighted_prizes[] = [
					'index'  => $index,
					'weight' => $probability,
					'data'   => $prize,
				];
			}
		}

		/**
		 * Hook: uspw_before_prize_selection
		 * 
		 * Fires before the prize selection algorithm runs.
		 * Useful for analytics tracking, logging, or A/B testing.
		 * 
		 * @param int   $campaign_id      Campaign ID
		 * @param array $weighted_prizes  Available prizes with weights
		 * @param array $user_data        User submitted data
		 * 
		 * @since 1.0.0
		 */
		if ( apply_filters( 'ultimate_spin_wheel_pro_init', false ) ) {
			do_action( 'uspw_before_prize_selection', $campaign_id, $weighted_prizes, $user_data );
		}

		// Select Winner
		$selected_index = -1;
		$selected_prize = null;

		if ( $total_weight > 0 ) {
			$rand           = wp_rand( 0, $total_weight * 100 ) / 100;
			$current_weight = 0;

			foreach ( $weighted_prizes as $item ) {
				$current_weight += $item['weight'];
				if ( $rand <= $current_weight ) {
					$selected_index = $item['index'];
					$selected_prize = $item['data'];
					break;
				}
			}
		}

		// Fallback/Failsafe if no prize selected despite having weights (unlikely)
		if ( $selected_index === -1 ) {
			if ( ! empty( $weighted_prizes ) ) {
				// Pick a random one from available weighted prizes as fallback
				$fallback_key   = array_rand( $weighted_prizes );
				$selected_index = $weighted_prizes[ $fallback_key ]['index'];
				$selected_prize = $weighted_prizes[ $fallback_key ]['data'];
			} else {
				// No prizes available
				wp_send_json_error( [ 'message' => esc_html__( 'No available prizes.', 'ultimate-spin-wheel' ) ] );
			}
		}

		// Consume Inventory / Generate Dynamic Coupon
		$won_code = '';
		if ( $selected_prize ) {
			$coupon_type = $selected_prize['coupon_type'] ?? 'static';

			if ( 'wc_dynamic' === $coupon_type ) {
				// Delegate to Pro if active
				$generated_code = apply_filters( 'uspw_generate_wc_dynamic_coupon', null, $selected_prize, isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '' );

				if ( $generated_code ) {
					$won_code = $generated_code;
				} else {
					// Fallback to static code if generation fails or Pro not active
					$won_code = $selected_prize['code'] ?? '';
				}
			} elseif ( ! empty( $selected_prize['is_unique'] ) ) {
				$_raw_code = trim( $selected_prize['code'] ?? '' );
				$codes     = array_filter( array_map( 'trim', explode( ',', $_raw_code ) ) );

				if ( ! empty( $codes ) ) {
					$won_code = array_shift( $codes );
					// Update DB
					$prizes[ $selected_index ]['code'] = implode( ', ', $codes );
					update_post_meta( $campaign_id, 'uspw_coupons', wp_json_encode( $prizes ) );
				}
			} else {
				$won_code = $selected_prize['code'] ?? '';
			}
		}

		/**
		 * Hook: uspw_after_prize_selected
		 * 
		 * Fires after prize is selected but before response is sent to user.
		 * Useful for notifications, inventory tracking, or custom logging.
		 * 
		 * @param array  $selected_prize  The winning prize details
		 * @param int    $campaign_id     Campaign ID
		 * @param array  $user_data       User submitted data
		 * @param string $won_code        The actual coupon code won
		 * 
		 * @since 1.0.0
		 */
		if ( apply_filters( 'ultimate_spin_wheel_pro_init', false ) ) {
			do_action( 'uspw_after_prize_selected', $selected_prize, $campaign_id, $user_data, $won_code );
		}

		// Status is 'lose' if coupon type is 'lose', otherwise 'won'
	$coupon_type = $selected_prize['coupon_type'] ?? 'static';
	$status = ( 'lose' === $coupon_type ) ? 'lose' : 'won';

		// Load Merged Settings
		$content_settings  = $this->get_merged_meta( $campaign_id, 'uspw_content_settings', 'contentSettings' );
		$behavior_settings = $this->get_merged_meta( $campaign_id, 'uspw_behavior_settings', 'behaviorSettings' );

		$prize_content  = $content_settings['prizeContent'] ?? [];
		$prize_behavior = $behavior_settings['prizeDelivery'] ?? [];

		$label = $selected_prize['label'] ?? '';

		$is_pro = apply_filters( 'ultimate_spin_wheel_pro_init', false );

	if ( 'lose' === $status ) {
		// Lose segment - use lose content
		$heading = $prize_content['loseTitle'] ?? esc_html__( 'Better Luck!', 'ultimate-spin-wheel' );
		// Use custom message if set, otherwise use global lose message
		$message = ! empty( $selected_prize['message'] ) ? $selected_prize['message'] : ( $prize_content['loseMessage'] ?? esc_html__( 'Try again next time!', 'ultimate-spin-wheel' ) );
	} else {
		// Win segment - use win content
		$heading = $prize_content['winTitle'] ?? esc_html__( 'Congratulations!', 'ultimate-spin-wheel' );
		// Use prize specific message if set (PRO), otherwise global win message
		$message = ( $is_pro && ! empty( $selected_prize['message'] ) ) ? $selected_prize['message'] : ( $prize_content['winMessage'] ?? esc_html__( 'You won a {{discount_label}} discount!', 'ultimate-spin-wheel' ) );
	}

		// Prepare Response
		$response_data = [
			'index'         => $selected_index,
			'label'         => $label,
			'code'          => $won_code,
			'color'         => $selected_prize['color'] ?? '',
			'status'        => $status,
			'heading'       => $heading,
			'message'       => $message,
			'segment_index' => $selected_index,
		];

		// ATTEMPT IMMEDIATE AUTO-APPLY (Server-Side ASAP)
		$is_pro     = apply_filters( 'ultimate_spin_wheel_pro_init', false );
		$auto_apply = $prize_behavior['autoApplyCoupon'] ?? false;
		if ( $is_pro && 'won' === $status && $auto_apply && ! empty( $won_code ) && function_exists( 'WC' ) && WC()->cart ) {
			if ( WC()->session && ! WC()->session->has_session() ) {
				WC()->session->set_customer_session_cookie( true );
			}
			if ( ! WC()->cart->has_discount( $won_code ) ) {
				WC()->cart->apply_coupon( $won_code );
			}
		}

		// Lead Verification (Email Delivery) Check
		$send_on_email = $prize_behavior['emailDelivery'] ?? 'no';

		if ( 'won' === $status && 'yes' === $send_on_email ) {
			$response_data['code']    = ''; // Hide coupon from UI
			$response_data['message'] = $prize_behavior['emailDeliveryMsg']
				?: esc_html__( 'Check your email for the coupon code!', 'ultimate-spin-wheel' );
		}

		// Save detailed entry immediately (Server-Side)
		$user_data = [
			'name'           => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'email'          => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
			'phone'          => isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '',
			'campaign_id'    => $campaign_id,
			'campaign_title' => isset( $_POST['campaign_title'] ) ? sanitize_text_field( wp_unslash( $_POST['campaign_title'] ) ) : get_the_title( $campaign_id ),
			'coupon_code'    => $won_code,
			'coupon_title'   => $response_data['label'],
			'status'         => $status,
			'segment_index'  => $selected_index,
			'optin'          => isset( $_POST['optin'] ) ? intval( wp_unslash( $_POST['optin'] ) ) : 0,
			'message'        => $response_data['message'], // Custom Win/Lose Message
		];
		$this->save_spin_entry( $user_data );

		/*
		 * Hook: uspw_after_coupon_won
		 * Ensure this hook fires for the AJAX spin flow too!
		 */
		if ( apply_filters( 'ultimate_spin_wheel_pro_init', false ) ) {
			do_action( 'uspw_after_coupon_won', $campaign_id, $user_data );
		}

		wp_send_json_success( $response_data );
	}

	/**
	 * Helper to save spin entry
	 */
	private function save_spin_entry( $args ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'wdengage_entries';

		$defaults = [
			'campaign_id'    => 0,
			'campaign_title' => '',
			'name'           => '',
			'email'          => '',
			'phone'          => '',
			'campaign_type'  => 'Spin Wheel',
			'user_data' => json_encode([
				'ip_address' => $this->get_user_ip(),
				'device_id'  => $this->get_device_id(),
				'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
			]),
		];

		$data = shortcode_atts( $defaults, $args );

		/**
		 * Hook: uspw_before_save_entry
		 * 
		 * Allows modification of entry data before saving to database.
		 * Useful for data enrichment, adding custom fields, or UTM tracking.
		 * 
		 * @param array $data        Entry data
		 * @param int   $campaign_id Campaign ID
		 * 
		 * @since 1.0.0
		 */
		if ( apply_filters( 'ultimate_spin_wheel_pro_init', false ) ) {
			$data = apply_filters( 'uspw_before_save_entry', $data, $args['campaign_id'] );
		}

		// Map specific fields for JSON columns if needed, matching spin_wheel_spinned
		// We use $data values here to ensure any modifications in the hook are preserved
		$data['others_data'] = json_encode([
			'coupon_title' => $args['coupon_title'],
			'coupon_code'  => $args['coupon_code'],
			'status'       => $args['status'],
			'phone'        => $data['phone'], // Use modified phone
		]);

		$db_data = [
			'campaign_id'    => $data['campaign_id'],
			'campaign_title' => $data['campaign_title'],
			'name'           => $data['name'],
			'email'          => $data['email'],
			'phone'          => $data['phone'],
			'others_data'    => $data['others_data'],
			'user_data'      => $data['user_data'],
			'campaign_type'  => 'Spin Wheel',
			'optin'          => isset( $args['optin'] ) ? intval( $args['optin'] ) : 0,
			'created_at'     => current_time( 'mysql', 1 ),
		];

		$wpdb->insert( $table_name, $db_data );
		$entry_id = $wpdb->insert_id;

		/**
		 * Hook: uspw_after_entry_saved
		 * 
		 * Fires after entry is saved to database.
		 * Useful for CRM sync, webhook triggers, or external integrations.
		 * 
		 * @param int   $entry_id    Database entry ID
		 * @param array $data        Entry data
		 * @param int   $campaign_id Campaign ID
		 * 
		 * @since 1.0.0
		 */
		if ( apply_filters( 'ultimate_spin_wheel_pro_init', false ) ) {
			do_action( 'uspw_after_entry_saved', $entry_id, $data, $args['campaign_id'] );
		}

		// Email Trigger (Lead Verification / Email Delivery)
		if ( 'won' === $args['status'] && ! empty( $data['email'] ) && ! empty( $args['coupon_code'] ) ) {
			$behavior_settings = $this->get_merged_meta( $data['campaign_id'], 'uspw_behavior_settings', 'behaviorSettings' );
			$prize_behavior    = $behavior_settings['prizeDelivery'] ?? [];
			$send_on_email     = $prize_behavior['emailDelivery'] ?? 'no';

			if ( 'yes' === $send_on_email ) {
				/* translators: %s: Site name */
				$subject = sprintf( esc_html__( 'Your Reward from %s', 'ultimate-spin-wheel' ), get_bloginfo( 'name' ) );
				$headers = [ 'Content-Type: text/html; charset=UTF-8' ];
				$message = $this->get_email_template([
					'site_name'    => get_bloginfo( 'name' ),
					'site_url'     => home_url(),
					'coupon_title' => $args['coupon_title'],
					'coupon_code'  => $args['coupon_code'],
				]);

				/**
				 * Hook: uspw_before_send_winner_email
				 * 
				 * Allows modification of email parameters before sending.
				 * Useful for custom routing, translation, or personalization.
				 * Return an empty 'to' address to skip sending.
				 * 
				 * @param array $email_args  Email data (to, subject, message, headers)
				 * @param array $data        Entry data
				 * @param int   $campaign_id Campaign ID
				 * 
				 * @since 1.0.0
				 */
				$email_args = [
					'to'      => $data['email'],
					'subject' => $subject,
					'message' => $message,
					'headers' => $headers,
				];
				if ( apply_filters( 'ultimate_spin_wheel_pro_init', false ) ) {
					$email_args = apply_filters( 'uspw_before_send_winner_email', $email_args, $data, $args['campaign_id'] );
				}

				if ( ! empty( $email_args['to'] ) ) {
					$email_sent = wp_mail( $email_args['to'], $email_args['subject'], $email_args['message'], $email_args['headers'] );
				} else {
					$email_sent = false; // Skipped
				}

				/**
				 * Hook: uspw_email_sent_result
				 * 
				 * Fires after email send attempt.
				 * Useful for logging failures or implementing retry logic.
				 * 
				 * @param bool  $email_sent  Whether email was sent successfully
				 * @param array $data        Entry data
				 * @param int   $campaign_id Campaign ID
				 * 
				 * @since 1.0.0
				 */
				if ( apply_filters( 'ultimate_spin_wheel_pro_init', false ) ) {
					do_action( 'uspw_email_sent_result', $email_sent, $data, $args['campaign_id'] );
				}
			}
		}

		// Trigger Integrations Queue
		if ( class_exists( '\USPIN_WHEEL\Includes\Core\Integrations' ) ) {
			\USPIN_WHEEL\Includes\Core\Integrations::instance()->enqueue_lead( $data['campaign_id'], $data, $entry_id );
		}
	}

	/**
	 * Load default settings from centralized JSON
	 */
	public function get_defaults() {
		if ( null !== $this->defaults ) {
			return $this->defaults;
		}

		$json_path = USPIN_WHEEL_PATH . 'src/campaign/defaults.json';
		if ( file_exists( $json_path ) ) {
			$json_content   = file_get_contents( $json_path );
			$this->defaults = json_decode( $json_content, true );
		} else {
			$this->defaults = [];
		}

		return $this->defaults;
	}

	/**
	 * Get merged meta with defaults
	 */
	public function get_merged_meta( $post_id, $meta_key, $default_key ) {
		$meta_raw = get_post_meta( $post_id, $meta_key, true );
		if ( is_string( $meta_raw ) ) {
			$meta = json_decode( $meta_raw, true );
		} else {
			$meta = $meta_raw;
		}

		if ( ! is_array( $meta ) ) {
			$meta = [];
		}

		$defaults    = $this->get_defaults();
		$default_val = $defaults[ $default_key ] ?? [];

		return ! empty( $meta ) ? wp_parse_args( $meta, $default_val ) : $default_val;
	}

	/**
	 * Get global settings with caching to avoid multiple get_option calls
	 * Performance optimization: Calls get_option only once per request
	 */
	private function get_global_settings() {
		if ( $this->global_settings === null ) {
			$settings = get_option( 'uspw_global_settings', [] );
			if ( is_string( $settings ) ) {
				$settings = json_decode( $settings, true );
			}
			$this->global_settings = is_array( $settings ) ? $settings : [];
		}
		return $this->global_settings;
	}

	/**
	 * Inject dynamic styles based on design settings
	 */
	public function inject_dynamic_styles() {
		$design = $this->settings['design_settings'] ?? [];
		$popup  = $design['popup'] ?? [];
		$form   = $design['form'] ?? [];

		// Get defaults for fallbacks
		$defaults       = $this->get_defaults();
		$default_design = $defaults['designSettings'] ?? [];
		$default_popup  = $default_design['popup'] ?? [];
		$default_form   = $default_design['form'] ?? [];

		$styles = [
			// Popup Base
			'--uspw-popup-bg'                => $popup['backgroundColor'] ?? $default_popup['backgroundColor'] ?? '#fff',
			'--uspw-popup-padding'           => ( $popup['padding'] ?? $default_popup['padding'] ?? '20' ) . 'px',
			'--uspw-popup-border-radius'     => ( $popup['borderRadius'] ?? $default_popup['borderRadius'] ?? '12' ) . 'px',
			'--uspw-popup-max-width'         => ( $popup['maxWidth'] ?? $default_popup['maxWidth'] ?? '800' ) . 'px',

			// Close Button
			'--uspw-close-bg'                => $popup['closeButtonBackgroundColor'] ?? $default_popup['closeButtonBackgroundColor'] ?? '#f5576c',
			'--uspw-close-color'             => $popup['closeButtonColor'] ?? $default_popup['closeButtonColor'] ?? '#fff',

			// Form Typography
			'--uspw-form-title-size'         => ( $form['title']['fontSize'] ?? $default_form['title']['fontSize'] ?? '24' ) . 'px',
			'--uspw-form-title-color'        => $form['title']['color'] ?? $default_form['title']['color'] ?? '#1a1a1a',
			'--uspw-form-desc-size'          => ( $form['description']['fontSize'] ?? $default_form['description']['fontSize'] ?? '15' ) . 'px',
			'--uspw-form-desc-color'         => $form['description']['color'] ?? $default_form['description']['color'] ?? '#6b7280',
			'--uspw-form-label-size'         => ( $form['label']['fontSize'] ?? $default_form['label']['fontSize'] ?? '14' ) . 'px',
			'--uspw-form-label-color'        => $form['label']['color'] ?? $default_form['label']['color'] ?? '#374151',

			// Form Inputs
			'--uspw-form-input-color'        => $form['inputBox']['color'] ?? $default_form['inputBox']['color'] ?? '#1a1a1a',
			'--uspw-form-input-bg'           => $form['inputBox']['backgroundColor'] ?? $default_form['inputBox']['backgroundColor'] ?? '#fff',
			'--uspw-form-input-border'       => $form['inputBox']['borderColor'] ?? $default_form['inputBox']['borderColor'] ?? '#e5e7eb',
			'--uspw-form-input-focus-border' => $form['inputBox']['focusBorderColor'] ?? $default_form['inputBox']['focusBorderColor'] ?? '#667eea',
			'--uspw-form-input-radius'       => '8px', // Hardcoded or from defaults if added later

			// Submit Button
			'--uspw-form-submit-color'       => $form['submitButton']['color'] ?? $default_form['submitButton']['color'] ?? '#fff',
			'--uspw-form-submit-bg'          => $form['submitButton']['backgroundColor'] ?? $default_form['submitButton']['backgroundColor'] ?? '#4f46e5',
			'--uspw-form-submit-radius'      => ( $form['submitButton']['borderRadius'] ?? $default_form['submitButton']['borderRadius'] ?? '8' ) . 'px',
			'--uspw-form-submit-padding'     => ( $form['submitButton']['padding'] ?? $default_form['submitButton']['padding'] ?? '12' ) . 'px',
		];

		// Handle Popup Background Gradient
		if ( ( $popup['backgroundGradient'] ?? 'no' ) === 'yes' && ! empty( $popup['backgroundColor'] ) ) {
			$styles['--uspw-popup-bg'] = $popup['backgroundColor'];
		}

		// Handle Submit Button Gradient
		if ( ! empty( $form['submitButton']['backgroundGradient'] ) ) {
			$styles['--uspw-form-submit-bg'] = $form['submitButton']['backgroundGradient'];
		}

		// Handle Submit Button Hover bg
		if ( ! empty( $form['submitButton']['hoverBackgroundColor'] ) ) {
			$styles['--uspw-form-submit-hover-bg'] = $form['submitButton']['hoverBackgroundColor'];
		}
		if ( ! empty( $form['submitButton']['hoverBackgroundGradient'] ) ) {
			$styles['--uspw-form-submit-hover-bg'] = $form['submitButton']['hoverBackgroundGradient'];
		}

		echo '<style id="uspw-dynamic-styles">';
		echo ':root {';
		foreach ( $styles as $prop => $val ) {
			echo esc_html( $prop ) . ': ' . esc_html( $val ) . ';';
		}
		echo '}';
		echo '</style>';
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
		 * Check if user is blocked (IP or Device ID)
		 */
		if ( $this->is_blocked() ) {
			return false;
		}

		/**
		 * Check if spin wheel is active
		 */
		if ( ! $this->is_spin_wheel_active() ) {
			return false;
		}

		$post_data = $this->is_spin_wheel_active();
		$post_id   = $post_data->post_id ?? 0;
		$meta      = $post_data->meta ?? [];

		$defaults = $this->get_defaults();

		$display_on = json_decode( $meta['uspw_display_on'][0] ?? '', true );
		if ( empty( $display_on ) ) {
			$display_on = $defaults['displayOn'] ?? [];
		}

		$not_display_on = json_decode( $meta['uspw_not_display_on'][0] ?? '', true );
		if ( empty( $not_display_on ) ) {
			$not_display_on = $defaults['notDisplayOn'] ?? [];
		}

		$display_special = json_decode( $meta['uspw_display_special_pages'][0] ?? '', true );
		if ( empty( $display_special ) ) {
			$display_special = $defaults['displaySpecialPages'] ?? [];
		}

		$not_display_special = json_decode( $meta['uspw_not_display_special_pages'][0] ?? '', true );
		if ( empty( $not_display_special ) ) {
			$not_display_special = $defaults['notDisplaySpecialPages'] ?? [];
		}

		$display_custom = json_decode( $meta['uspw_display_custom_pages'][0] ?? '', true );
		if ( empty( $display_custom ) ) {
			$display_custom = $defaults['displayCustomPages'] ?? [];
		}

		$not_display_custom = json_decode( $meta['uspw_not_display_custom_pages'][0] ?? '', true );
		if ( empty( $not_display_custom ) ) {
			$not_display_custom = $defaults['notDisplayCustomPages'] ?? [];
		}

		$display_roles = json_decode( $meta['uspw_display_roles'][0] ?? '', true );
		if ( empty( $display_roles ) ) {
			$display_roles = $defaults['userRoles'] ?? [];
		}

		$should_display = $this->should_display_conditions( $display_on, $not_display_on, $display_special, $not_display_special, $display_custom, $not_display_custom, $display_roles );

		if ( isset( $_GET['spin_wheel'] ) && isset( $_GET['_wpnonce'] ) && 'preview' === sanitize_text_field( wp_unslash( $_GET['spin_wheel'] ) ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'ultimate_spin_wheel' ) ) {
			$should_display = true; // Always display in preview mode
			$post_id        = isset( $_GET['campaign_id'] ) ? intval( wp_unslash( $_GET['campaign_id'] ) ) : $post_id;
			$meta           = get_post_meta( $post_id ); // Get post meta for preview
			return [
				'post_id' => $post_id,
				'meta'    => $meta,
			];
		}

		if ( isset( $_GET['spin_wheel'] ) && isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'ultimate_spin_wheel' ) ) {
			return false; // Invalid nonce, do not display
		}

		if ( ! $should_display ) {
			return false;
		}

		return [
			'post_id' => $post_id,
			'meta'    => $meta,
		];
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

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$is_preview = isset( $_GET['spin_wheel'] ) && 'preview' === $_GET['spin_wheel'];

		if ( ! $is_preview && 0 !== $max_impressions && $users_impressions >= $max_impressions ) {
			return false;
		}

		/**
		 * Get user name and email
		 */
		$user_name  = is_user_logged_in() ? wp_get_current_user()->display_name : '';
		$user_email = is_user_logged_in() ? wp_get_current_user()->user_email : '';

		// Load centralized defaults
		$defaults = $this->get_defaults();

		$campaign_settings = isset( $spin_wheel_post->meta['uspw_campaign_settings'] ) ? json_decode( $spin_wheel_post->meta['uspw_campaign_settings'][0], true ) : [];
		if ( empty( $campaign_settings ) ) {
			// Backward compatibility for 'behavior'
			$campaign_settings = isset( $spin_wheel_post->meta['uspw_behavior'] ) ? json_decode( $spin_wheel_post->meta['uspw_behavior'][0], true ) : $defaults['campaignSettings'] ?? [];
		} else {
			$campaign_settings = wp_parse_args( $campaign_settings, $defaults['campaignSettings'] ?? [] );
		}

		$form_settings = isset( $spin_wheel_post->meta['uspw_form_settings'] ) ? json_decode( $spin_wheel_post->meta['uspw_form_settings'][0], true ) : [];
		$form_settings = ! empty( $form_settings ) ? wp_parse_args( $form_settings, $defaults['formSettings'] ?? [] ) : ( $defaults['formSettings'] ?? [] );

		$content_settings = isset( $spin_wheel_post->meta['uspw_content_settings'] ) ? json_decode( $spin_wheel_post->meta['uspw_content_settings'][0], true ) : [];
		$content_settings = ! empty( $content_settings ) ? wp_parse_args( $content_settings, $defaults['contentSettings'] ?? [] ) : ( $defaults['contentSettings'] ?? [] );

		$design_settings = isset( $spin_wheel_post->meta['uspw_design_settings'] ) ? json_decode( $spin_wheel_post->meta['uspw_design_settings'][0], true ) : [];
		$design_settings = ! empty( $design_settings ) ? wp_parse_args( $design_settings, $defaults['designSettings'] ?? [] ) : ( $defaults['designSettings'] ?? [] );

		$this->settings = [
			// Core Campaign Data
			'post_id'                => $spin_wheel_post->post_id,
			'post_title'             => get_the_title( $spin_wheel_post->post_id ),
			'user_ip'                => $this->get_user_ip(),
			'user_name'              => $user_name,
			'email'                  => $user_email,
			'prizes' => ( function () use ( $spin_wheel_post, $defaults ) {
				$prizes = $this->get_coupons_display( $spin_wheel_post->meta['uspw_coupons'] ?? [] );
				if ( empty( $prizes ) && ! empty( $defaults['coupons'] ) ) {
					$prizes = $this->get_coupons_display( $defaults['coupons'] );
				}
				return $prizes;
			} )(),
			'plugin_url'             => USPIN_WHEEL_URL,

			// Campaign Behavior Settings (Two-Tier Frequency Model)
			'on_dismiss_behavior'    => ( $campaign_settings['onDismiss'] ?? [] )['behavior'] ?? 'hours',
			'on_dismiss_hours'       => intval( ( $campaign_settings['onDismiss'] ?? [] )['hours'] ?? 6 ),
			'on_dismiss_days'        => intval( ( $campaign_settings['onDismiss'] ?? [] )['days'] ?? 1 ),
			'on_play_behavior'       => ( $campaign_settings['onPlay'] ?? [] )['behavior'] ?? 'forever',
			'on_play_days'           => intval( ( $campaign_settings['onPlay'] ?? [] )['days'] ?? 30 ),
			'on_play_hours'          => intval( ( $campaign_settings['onPlay'] ?? [] )['hours'] ?? 24 ),
			'hide_on_mobile'         => ( ( $campaign_settings['visibility'] ?? [] )['hideOnMobile'] ?? false ) ? 'yes' : 'no',
			'hide_on_desktop'        => ( ( $campaign_settings['visibility'] ?? [] )['hideOnDesktop'] ?? false ) ? 'yes' : 'no',
			'sticky_reopen_button'   => ( $campaign_settings['triggers'] ?? [] )['stickyReopenButton'] ?? 'no',
			'sticky_reopen_icon'     => ( $campaign_settings['triggers'] ?? [] )['stickyReopenIcon'] ?? '1',
			'sticky_reopen_position' => ( $campaign_settings['triggers'] ?? [] )['stickyReopenPosition'] ?? 'right',

			// Form Settings (from formSettings JSON)
			'limit_spin_per_user'    => ( $form_settings['restrictSpinPerUser'] ?? [] )['limitSpinPerUser'] ?? 'yes',
			'identity_check_type'    => ( $form_settings['restrictSpinPerUser'] ?? [] )['identityCheckType'] ?? 'email',
			'already_played_message' => ( $form_settings['restrictSpinPerUser'] ?? [] )['alreadyPlayedMessage'] ?? esc_html__( 'You have already played this campaign!', 'ultimate-spin-wheel' ),
			'send_coupon_on_email'   => ( $form_settings['spamFilter'] ?? [] )['sendCouponOnEmail'] ?? 'no',
			'coupon_sent_message'    => ( $form_settings['spamFilter'] ?? [] )['couponSentMessage'] ?? esc_html__( 'Check your email for the coupon code!', 'ultimate-spin-wheel' ),
			'show_name_field'        => ( $form_settings['inputName']['enable'] ?? true ) ? 'yes' : 'no',
			'show_email_field'       => ( $form_settings['inputEmail']['enable'] ?? true ) ? 'yes' : 'no',
			'show_phone_field'       => ( ( $form_settings['inputPhone']['enable'] ?? false ) && apply_filters( 'ultimate_spin_wheel_pro_init', false ) ) ? 'yes' : 'no',

			// Design Settings (from designSettings JSON)
			'enable_confetti'        => ( ( $design_settings['popup']['enableConfetti'] ?? 'yes' ) === 'yes' && apply_filters( 'ultimate_spin_wheel_pro_init', false ) ) ? 'yes' : 'no',
			'enable_sound'           => ( ( $design_settings['popup']['enableSound'] ?? 'yes' ) === 'yes' && apply_filters( 'ultimate_spin_wheel_pro_init', false ) ) ? 'yes' : 'no',
			'celebration_sound' => ( function () use ( $design_settings ) {
				$sound = $design_settings['popup']['celebrationSound'] ?? '';
				if ( empty( $sound ) ) {
					return plugins_url( 'assets/others/celebration.mp3', USPIN_WHEEL__FILE__ );
				}
				if ( strpos( $sound, 'http' ) === 0 ) {
					return $sound;
				}

				return plugins_url( ltrim( $sound, '/' ), USPIN_WHEEL__FILE__ );
			} )(),

			// Full JSON Objects for Frontend
			'campaign_settings'      => $campaign_settings,
			'form_settings'          => $form_settings,
			'content_settings'       => $content_settings,
			'design_settings'        => ( function () use ( $design_settings ) {
				// Enforce Pro-only restrictions on backend
				if ( ! apply_filters( 'ultimate_spin_wheel_pro_init', false ) ) {
					if ( isset( $design_settings['wheel'] ) ) {
						$wheel = $design_settings['wheel'];
						// Allow only basic themes
						$allowed_themes = [ 'default', 'lucky-gold', 'neon-night' ];
						$preset_id = $wheel['presetId'] ?? 'default';
						if ( ! in_array( $preset_id, $allowed_themes ) ) {
							$preset_id = 'default';
						}
						
						// Enforce the allowed preset and preserve essential wheel components (blocks, buttons, styles)
						$design_settings['wheel'] = [
							'presetId'     => $preset_id,
							'blocks'       => isset( $wheel['blocks'] ) ? $wheel['blocks'] : [],
							'buttons'      => isset( $wheel['buttons'] ) ? $wheel['buttons'] : [],
							'defaultStyle' => isset( $wheel['defaultStyle'] ) ? $wheel['defaultStyle'] : [],
						];

						// Note: Advanced styling like 'imageStyle' is omitted for non-pro
					}
				}
				return $design_settings;
			} )(),
			'max_impressions'        => $max_impressions,
			'impressions_count'      => $users_impressions,
		];

		return $spin_wheel_post;
	}

	/**
	 * Check if a campaign is currently scheduled to be shown
	 */
	private function is_campaign_scheduled( $meta ) {
		$is_pro = apply_filters( 'ultimate_spin_wheel_pro_init', false );
		if ( ! $is_pro ) {
			return true;
		}

		$defaults      = $this->get_defaults();
		$schedule_type = $meta['uspw_schedule_type'][0] ?? $defaults['scheduleType'] ?? 'always';

		if ( $schedule_type === 'always' ) {
			return true;
		}

		$start_date = $meta['uspw_start_date'][0] ?? '';
		$end_date   = $meta['uspw_end_date'][0] ?? '';
		$now        = current_time( 'mysql' );

		if ( ! empty( $start_date ) && $now < $start_date ) {
			return false;
		}

		if ( ! empty( $end_date ) && $now > $end_date ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the current spin wheel post based on conditions
	 * Improved version with better post selection logic
	 */
	private function get_current_spin_wheel_post() {
		$args = [
			'post_type'      => $this->post_type,
			'posts_per_page' => -1, // Get all matching posts for better selection
			'post_status'    => 'publish',
			'meta_query' => [
				'relation' => 'AND',
				[
					'key'     => 'uspw_type',
					'value'   => 'spin_wheel',
					'compare' => '=',
				],
			],
			'orderby'        => 'date',
			'order'          => 'DESC', // Most recent first
		];

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
			$args['meta_query'][] = [
				'key'     => 'uspw_status',
				'value'   => 'enabled',
				'compare' => '=',
			];
		}

		$posts = get_posts( $args );

		if ( empty( $posts ) ) {
			return null;
		}

		// If preview mode or only one post, return it directly
		if ( count( $posts ) === 1 ) {
			$post = $posts[0];
			$meta = get_post_meta( $post->ID );

			// Check schedule if not preview mode
			if ( ! isset( $_GET['campaign_id'] ) && ! $this->is_campaign_scheduled( $meta ) ) {
				wp_reset_postdata();
				return null;
			}

			$filtered_meta = $this->filter_meta_keys( $meta );
			wp_reset_postdata();
			return (object) [
				'post_id' => $post->ID,
				'meta'    => $filtered_meta,
			];
		}

		// Multiple posts found - apply selection logic
		$selected_post = $this->select_best_matching_post( $posts );

		if ( $selected_post ) {
			$meta          = get_post_meta( $selected_post->ID );
			$filtered_meta = $this->filter_meta_keys( $meta );
			wp_reset_postdata();
			return (object) [
				'post_id' => $selected_post->ID,
				'meta'    => $filtered_meta,
			];
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

		$scored_posts = [];

		foreach ( $posts as $candidate_post ) {
			$meta  = get_post_meta( $candidate_post->ID );
			$score = 0;

			// Get centralized defaults
			$defaults = $this->get_defaults();

			// Get display conditions with fallbacks
			$display_on = json_decode( $meta['uspw_display_on'][0] ?? '', true );
			if ( empty( $display_on ) ) {
				$display_on = $defaults['displayOn'] ?? [];
			}

			$not_display_on = json_decode( $meta['uspw_not_display_on'][0] ?? '', true );
			if ( empty( $not_display_on ) ) {
				$not_display_on = $defaults['notDisplayOn'] ?? [];
			}

			$display_special = json_decode( $meta['uspw_display_special_pages'][0] ?? '', true );
			if ( empty( $display_special ) ) {
				$display_special = $defaults['displaySpecialPages'] ?? [];
			}

			$not_display_special = json_decode( $meta['uspw_not_display_special_pages'][0] ?? '', true );
			if ( empty( $not_display_special ) ) {
				$not_display_special = $defaults['notDisplaySpecialPages'] ?? [];
			}

			$display_custom = json_decode( $meta['uspw_display_custom_pages'][0] ?? '', true );
			if ( empty( $display_custom ) ) {
				$display_custom = $defaults['displayCustomPages'] ?? [];
			}

			$not_display_custom = json_decode( $meta['uspw_not_display_custom_pages'][0] ?? '', true );
			if ( empty( $not_display_custom ) ) {
				$not_display_custom = $defaults['notDisplayCustomPages'] ?? [];
			}

			$display_roles = json_decode( $meta['uspw_display_roles'][0] ?? '', true );
			if ( empty( $display_roles ) ) {
				$display_roles = $defaults['userRoles'] ?? [];
			}

			// Check schedule first (Performance optimization)
			if ( ! $this->is_campaign_scheduled( $meta ) ) {
				continue;
			}

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
				$user_roles = array_diff( $display_roles, [ 'logged_in', 'logged_out' ] );
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

			$scored_posts[] = [
				'post'  => $candidate_post,
				'score' => $score,
			];
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
		$filtered_meta = [];
		foreach ( $meta as $key => $value ) {
			if ( strpos( $key, 'uspw_' ) === 0 ) {
				$filtered_meta[ $key ] = $value;
			}
		}
		return $filtered_meta;
	}

	/**
	 * Get user IP address (Enhanced)
	 */
	public function get_user_ip() {
		$ip = '';
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} else {
			$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		}

		// Handle comma separated IPs
		if ( strpos( $ip, ',' ) !== false ) {
			$ips = explode( ',', $ip );
			$ip  = trim( $ips[0] );
		}

		return $ip;
	}

	/**
	 * Get or set unique device ID via persistent cookie
	 */
	public function get_device_id() {
		$cookie_name = 'uspw_device_id';

		if ( isset( $_COOKIE[ $cookie_name ] ) ) {
			return sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
		}

		// Generate new device ID if not set
		$device_id = wp_generate_password( 32, false );

		// Set cookie for 1 year
		if ( ! headers_sent() ) {
			setcookie( $cookie_name, $device_id, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
		}

		return $device_id;
	}

	/**
	 * Check if current user is blocked
	 */
	public function is_blocked() {
		$settings = $this->get_global_settings();
		if ( empty( $settings ) ) {
			return false;
		}
		$security = $settings['security'] ?? [];

		$blocked_ips     = $security['blocked_ips'] ?? [];
		$blocked_devices = $security['blocked_devices'] ?? [];

		$current_ip     = $this->get_user_ip();
		$current_device = $this->get_device_id();

		if ( in_array( $current_ip, $blocked_ips, true ) ) {
			return true;
		}

		if ( in_array( $current_device, $blocked_devices, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Determine if a template should be displayed
	 * All Server side logic for displaying the spin wheel
	 */
	private function should_display_conditions( $display_on, $not_display_on, $display_special, $not_display_special, $display_custom, $not_display_custom, $display_roles ) {
		$is_pro = apply_filters( 'ultimate_spin_wheel_pro_init', false );

		if ( ! $is_pro ) {
			$display_on     = array_diff( $display_on, [ 'custom_pages' ] );
			$not_display_on = array_diff( $not_display_on, [ 'custom_pages' ] );
		}

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
				$user_roles = array_diff( $display_roles, [ 'logged_in', 'logged_out' ] ); // Remove auth states

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
			$excluded_roles = array_diff( $not_display_roles ?? [], [ 'logged_in', 'logged_out' ] );

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
			return [];
		}

		// If $coupons is an array with a single JSON string, decode it
		if ( is_array( $coupons ) && count( $coupons ) === 1 && is_string( $coupons[0] ) ) {
			$coupons = json_decode( $coupons[0], true );
		} elseif ( is_string( $coupons ) ) {
			$coupons = json_decode( $coupons, true );
		}

		if ( ! is_array( $coupons ) ) {
			return [];
		}

		$result = [];
		$is_pro = apply_filters( 'ultimate_spin_wheel_pro_init', false );

		foreach ( $coupons as $index => $coupon ) {
			// PRO FEATURE: WooCommerce Dynamic Coupon - Filter out if not pro
			if ( isset( $coupon['coupon_type'] ) && 'wc_dynamic' === $coupon['coupon_type'] && ! $is_pro ) {
				continue;
			}

			if ( isset( $coupon['label'] ) && isset( $coupon['color'] ) ) {
				$probability = isset( $coupon['probability'] ) ? floatval( $coupon['probability'] ) : 1;

				// UNIQUE COUPON EXHAUSTION
				$is_exhausted = false;
				if ( ! empty( $coupon['is_unique'] ) ) {
					$_coupon_code = trim( $coupon['code'] ?? '' );
					$codes        = array_filter( array_map( 'trim', explode( ',', $_coupon_code ) ) );
					if ( empty( $codes ) ) {
						$is_exhausted = true;
					}
				}

				$coupon_data = [
					'label'        => $is_exhausted ? '' : $coupon['label'],
					'color'        => $coupon['color'],
					'code'         => 'USPW_SECURE_FETCH', // No longer sending real code to DOM
					'probability'  => $probability,
					'index'        => $index, // Add index for identifier
					'is_exhausted' => $is_exhausted,
				];

				// PRO FEATURES: Individual Win Message and Segment Icon/Image
				if ( $is_pro ) {
					$coupon_data['message']   = $coupon['message'] ?? '';
					$coupon_data['image_url'] = $coupon['image_url'] ?? '';
				}

				$result[ $index ] = $coupon_data;
			}
		}

		return array_values( $result );
	}

	/**
	 * Handle impression count for the spin wheel
	 */
	public function impression_count() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimate_spin_wheel' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'ultimate-spin-wheel' ) ] );
		}

		$campaign_id = isset( $_POST['campaign_id'] ) ? intval( $_POST['campaign_id'] ) : 0;
		if ( ! $campaign_id ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid campaign ID', 'ultimate-spin-wheel' ) ] );
		}

		$count = get_post_meta( $campaign_id, 'uspw_impressions_count', true );
		$count = $count ? intval( $count ) : 0;
		update_post_meta( $campaign_id, 'uspw_impressions_count', $count + 1 );

		wp_send_json_success( [ 'count' => $count + 1 ] );
	}


	/**
	 * Enqueue scripts and styles for the spin wheel
	 */
	public function enqueue_scripts() {
		wp_register_script( 'micromodal', USPIN_WHEEL_ASSETS_URL . 'vendor/js/micromodal.min.js', [], '0.4.10', true );
		wp_enqueue_script( 'micromodal' );

		wp_register_script( 'lucky-canvas', USPIN_WHEEL_ASSETS_URL . 'vendor/js/lucky-canvas.umd.min.js', [ 'micromodal' ], '1.7.27', true );
		wp_enqueue_script( 'lucky-canvas' );

		// Enqueue confetti only if enabled
		$enable_confetti = isset( $this->settings['enable_confetti'] ) ? $this->settings['enable_confetti'] : 'yes';
		if ( 'yes' === $enable_confetti ) {
			wp_register_script( 'canvas-confetti', USPIN_WHEEL_ASSETS_URL . 'vendor/js/canvas-confetti.js', [], '1.6.0', true );
			wp_enqueue_script( 'canvas-confetti' );
		}

		wp_enqueue_style( 'spin-wheel', USPIN_WHEEL_ASSETS_URL . 'css/spin-wheel.css', [], USPIN_WHEEL_VERSION );
		wp_register_script( 'spin-wheel', USPIN_WHEEL_ASSETS_URL . 'js/spin-wheel.js', [ 'lucky-canvas', 'wp-i18n' ], USPIN_WHEEL_VERSION, true );
		wp_enqueue_script( 'spin-wheel' );

		// Localize script with data
		wp_localize_script(
			'spin-wheel',
			'USPW_UI_CONFIG',
			[
				'version'  => USPIN_WHEEL_VERSION,
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'ultimate_spin_wheel' ),
				'settings' => $this->settings,
			]
		);
	}
	/**
	 * Render the spin wheel HTML
	 */
	public function render_spin_wheel() {
		$spin_wheel_data = $this->spin_wheel_init();
		if ( ! $spin_wheel_data ) {
			return;
		}
		// Note: is_spin_wheel_active() already populated $this->settings with parsed data
		// Don't overwrite it - just use it directly

		$post_id = $spin_wheel_data['post_id'];
		$meta    = $spin_wheel_data['meta'];

		$campaign_id    = $post_id;
		$settings       = $this->settings;
		$custom_designs = $settings['custom_designs'] ?? [];

		// Extract settings for template (used by themes-default.php)
		$form_settings    = $settings['form_settings'] ?? [];
		$content_settings = $settings['content_settings'] ?? [];
		$design_settings  = $settings['design_settings'] ?? [];
		$coupons          = $settings['prizes'] ?? [];
		$global_settings  = $this->get_global_settings(); // Pass cached global settings to template

		$theme_path = USPIN_WHEEL_INCLUDES . 'core/themes-default.php';

		if ( file_exists( $theme_path ) ) {
			include $theme_path;
		}
	}


	/**
	 * Get HTML email template
	 *
	 * @param array $args Template arguments
	 * @return string HTML email content
	 */
	public function get_email_template( $args ) {
		$site_name    = $args['site_name'] ?? get_bloginfo( 'name' );
		$site_url     = $args['site_url'] ?? home_url();
		$coupon_title = $args['coupon_title'] ?? '';
		$coupon_code  = $args['coupon_code'] ?? '';

		// Check for pro version custom template via filter
		// We pass the args and if the filter returns a string, we use it
		$custom_content = apply_filters( 'uspw_custom_email_template', null, $args );

		if ( null !== $custom_content ) {
			return $custom_content;
		}

		// Fall back to default PHP template (Free Version)
		ob_start();
		include USPIN_WHEEL_INCLUDES . 'core/templates/email-coupon.php';
		return ob_get_clean();
	}

	/**
	 * Check if an email is from a disposable/temporary email provider
	 *
	 * @param string $email Email address to check
	 * @return bool True if disposable, false otherwise
	 */
	public function is_disposable_email( $email ) {
		if ( empty( $email ) ) {
			return false;
		}

		// Check if feature is enabled in global settings
		$settings          = $this->get_global_settings();
		$disposable_config = $settings['security']['disposable_email_block'] ?? [];

		if ( empty( $disposable_config['enabled'] ) ) {
			return false;
		}

		// Extract domain from email
		$email_parts = explode( '@', strtolower( trim( $email ) ) );
		if ( count( $email_parts ) !== 2 ) {
			return false;
		}
		$domain = $email_parts[1];

		// Load disposable domains from external file
		require_once USPIN_WHEEL_INCLUDES . 'core/disposable-domains.php';
		$disposable_domains = uspw_get_disposable_email_domains();

		return in_array( $domain, $disposable_domains, true );
	}
}
