<?php

namespace USPIN_WHEEL\Includes\Core;

defined( 'ABSPATH' ) || exit;

class Settings {


	private static $instance = null;
	private $cached_settings = null;

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'wp_ajax_ultimate_spin_wheel_get_global_settings', [ $this, 'get_global_settings_ajax' ] );
		add_action( 'wp_ajax_ultimate_spin_wheel_update_global_settings', [ $this, 'update_global_settings_ajax' ] );
	}

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Get the default settings structure
	 */
	public function get_default_settings() {
		return [
			'labels' => [
				'lead_stages' => [
					[
						'id'    => 'new',
						'label' => __( 'New', 'ultimate-spin-wheel' ),
						'color' => 'indigo',
						'icon'  => 'fa-star',
					],
					[
						'id'    => 'followup',
						'label' => __( 'Follow-up', 'ultimate-spin-wheel' ),
						'color' => 'amber',
						'icon'  => 'fa-phone',
					],
					[
						'id'    => 'purchased',
						'label' => __( 'Purchased', 'ultimate-spin-wheel' ),
						'color' => 'emerald',
						'icon'  => 'fa-cart-shopping',
					],
					[
						'id'    => 'archived',
						'label' => __( 'Archived', 'ultimate-spin-wheel' ),
						'color' => 'slate',
						'icon'  => 'fa-box-archive',
					],
					[
						'id'    => 'trash',
						'label' => __( 'Trash', 'ultimate-spin-wheel' ),
						'color' => 'red',
						'icon'  => 'fa-trash-can',
					],
				],
			],
			'api' => [
				'ai_provider'      => 'openai',
				'openai_key'       => '',
				'mailchimp_key'    => '',
				'mailchimp_list'   => '',
				'mailchimp_enable' => false,
				'zapier_webhook'   => '',
				'zapier_enable'    => false,
				'mailpoet_list'    => '',
				'mailpoet_enable'  => false,
			],
			'general' => [
				'default_timezone'           => 'UTC',
				'enable_email_notifications' => true,
				'data_retention_days'        => 365,
				'gdpr_enabled'               => false,
				'gdpr_label'                 => __( 'I agree to the privacy policy and terms.', 'ultimate-spin-wheel' ),
				'gdpr_required'              => true,
			],
			'security' => [
				'blocked_ips'     => [],
				'blocked_devices' => [],
			],
			'email' => [
				'enabled'         => false,
				'custom_template' => '',
			],
		];
	}

	/**
	 * Get global settings array merged with defaults and email template
	 */
	public function get_settings( $force_refresh = false ) {
		if ( $this->cached_settings !== null && ! $force_refresh ) {
			return $this->cached_settings;
		}

		$default_settings = $this->get_default_settings();
		$settings         = get_option( 'uspw_global_settings', [] );

		if ( is_string( $settings ) ) {
			$settings = json_decode( $settings, true );
		}

		if ( empty( $settings ) ) {
			$settings = $default_settings;
		} else {
			// Migration: Handle old format (formerly in class-entries.php)
			if ( isset( $settings['lead_pipeline'] ) ) {
				$stages                            = $settings['lead_pipeline']['stages'] ?? $default_settings['labels']['lead_stages'];
				$settings                          = $default_settings;
				$settings['labels']['lead_stages'] = $stages;
			} else {
				// Deep merge defaults with existing settings to ensure all keys exist
				foreach ( $default_settings as $key => $val ) {
					if ( ! isset( $settings[ $key ] ) ) {
						$settings[ $key ] = $val;
					} elseif ( is_array( $val ) && is_array( $settings[ $key ] ) ) {
						$settings[ $key ] = array_merge( $val, $settings[ $key ] );
					}
				}
			}
		}

		// Load custom template from separate option internally
		$custom_template                      = get_option( 'uspw_email_template', '' );
		$settings['email']['custom_template'] = $custom_template;

		$this->cached_settings = $settings;
		return $settings;
	}

	/**
	 * Save global settings
	 */
	public function save_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			return false;
		}

		// Handle Email Template separation
		if ( isset( $settings['email']['custom_template'] ) ) {
			$custom_template = $settings['email']['custom_template'];
			update_option( 'uspw_email_template', $custom_template );
			// Always remove from global settings to avoid bloating main options
			$settings['email']['custom_template'] = '';
		}

		update_option( 'uspw_global_settings', wp_json_encode( $settings ) );
		$this->cached_settings = null; // Invalidate cache
		return true;
	}

	/**
	 * AJAX Handler: Get global settings
	 */
	public function get_global_settings_ajax() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimate_spin_wheel' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'ultimate-spin-wheel' ) ] );
		}

		wp_send_json_success( $this->get_settings( true ) );
	}

	/**
	 * AJAX Handler: Update global settings
	 */
	public function update_global_settings_ajax() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		// Check for JSON POST
		$content_type = isset( $_SERVER['CONTENT_TYPE'] ) ? sanitize_mime_type( wp_unslash( $_SERVER['CONTENT_TYPE'] ) ) : '';
		if ( stripos( $content_type, 'application/json' ) !== false ) {
			$json_data = file_get_contents( 'php://input' );
			$_POST     = json_decode( $json_data, true ) ?: [];
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'ultimate_spin_wheel' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Invalid nonce', 'ultimate-spin-wheel' ) ] );
		}

		$settings_raw = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( empty( $settings_raw ) ) {
			wp_send_json_error( 'No settings provided' );
		}

		$settings = is_array( $settings_raw ) ? $settings_raw : json_decode( wp_unslash( $settings_raw ), true );

		if ( ! is_array( $settings ) ) {
			wp_send_json_error( 'Invalid JSON format' );
		}

		if ( $this->save_settings( $settings ) ) {
			wp_send_json_success( [ 'message' => __( 'Global settings updated successfully', 'ultimate-spin-wheel' ) ] );
		} else {
			wp_send_json_error( 'Failed to update settings' );
		}
	}
}
