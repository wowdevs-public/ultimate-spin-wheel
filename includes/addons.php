<?php

namespace USPIN_WHEEL;

defined( 'ABSPATH' ) || exit;

/**
 * The Admin class
 */
class Addons {

	const WIDGETS_DB_KEY = 'ultimate_spin_wheel_inactive_addons';
	const WIDGETS_3RD_PARTY_DB_KEY = 'ultimate_spin_wheel_inactive_3rd_party_widgets';
	const EXTENSIONS_DB_KEY = 'ultimate_spin_wheel_inactive_extensions';
	const API_DB_KEY = 'ultimate_spin_wheel_api';

	public static $widget_list = null;
	public static $widgets_name = null;

	public static function get_inactive_widgets() {
		return get_option( self::WIDGETS_DB_KEY, [] );
	}

	public static function get_inactive_3rd_party_widgets() {
		return get_option( self::WIDGETS_3RD_PARTY_DB_KEY, [] );
	}

	public static function get_inactive_extensions() {
		return get_option( self::EXTENSIONS_DB_KEY, [] );
	}

	public static function get_saved_api() {
		return get_option( self::API_DB_KEY, [] );
	}

	/**
	 * Get the demo server URL
	 *
	 * @return string
	 */
	public static function modules_demo_server() {
		return 'https://wowdevs.com/';
	}

	/**
	 * Elements List
	 */
	public static function get_element_list() {

		$inactive_widgets = self::get_inactive_widgets();
		$saved_api = self::get_saved_api();

		$widgets_fields = [
			'addons' => [
				[
					'name'         => 'spin-wheel',
					'label'        => esc_html__( 'Spin Wheel', 'ultimate-spin-wheel' ),
					'type'         => 'checkbox',
					'value'        => ! in_array( 'spin-wheel', $inactive_widgets ) ? 'on' : 'off',
					'default'      => 'on',
					'video_url'    => '#',
					'content_type' => 'custom',
					'feature_type' => 'free',
					'demo_url'     => self::modules_demo_server() . 'elementor-spin-wheel-widget/',
				],
				[
					'name'         => 'advanced-counter',
					'label'        => esc_html__( 'Advanced Counter', 'ultimate-spin-wheel' ),
					'type'         => 'checkbox',
					'value'        => ! in_array( 'advanced-counter', $inactive_widgets ) ? 'on' : 'off',
					'default'      => 'on',
					'video_url'    => '#',
					'content_type' => 'custom',
					'feature_type' => 'pro',
					'demo_url'     => self::modules_demo_server() . 'elementor-advanced-counter-widget/',
				],
				[
					'name'         => 'advanced-skill-bars',
					'label'        => esc_html__( 'Advanced Skill Bars', 'ultimate-spin-wheel' ),
					'type'         => 'checkbox',
					'value'        => ! in_array( 'advanced-skill-bars', $inactive_widgets ) ? 'on' : 'off',
					'default'      => 'on',
					'video_url'    => '#',
					'content_type' => 'custom',
					'feature_type' => 'free',
					'demo_url'     => self::modules_demo_server() . 'elementor-advanced-skill-bars-widget/',
				],
				[
					'name'         => 'advanced-slider',
					'label'        => esc_html__( 'Advanced Slider', 'ultimate-spin-wheel' ),
					'type'         => 'checkbox',
					'value'        => ! in_array( 'advanced-slider', $inactive_widgets ) ? 'on' : 'off',
					'default'      => 'on',
					'video_url'    => '#',
					'content_type' => 'custom',
					'feature_type' => 'free',
					'demo_url'     => self::modules_demo_server() . 'elementor-advanced-slider-widget/',
				],
				[
					'name'         => 'animated-heading',
					'label'        => esc_html__( 'Animated Heading', 'ultimate-spin-wheel' ),
					'type'         => 'checkbox',
					'value'        => ! in_array( 'animated-heading', $inactive_widgets ) ? 'on' : 'off',
					'default'      => 'on',
					'video_url'    => '#',
					'content_type' => 'custom',
					'feature_type' => 'free',
					'demo_url'     => self::modules_demo_server() . 'elementor-animated-heading-widget/',
				],
				[
					'name'         => 'audio-player',
					'label'        => esc_html__( 'Audio Player', 'ultimate-spin-wheel' ),
					'type'         => 'checkbox',
					'value'        => ! in_array( 'audio-player', $inactive_widgets ) ? 'on' : 'off',
					'default'      => 'on',
					'video_url'    => '#',
					'content_type' => 'custom',
					'feature_type' => 'free',
					'demo_url'     => self::modules_demo_server() . 'elementor-audio-player-widget/',
				],
				[
					'name'         => 'breadcrumbs',
					'label'        => esc_html__( 'Breadcrumbs', 'ultimate-spin-wheel' ),
					'type'         => 'checkbox',
					'value'        => ! in_array( 'breadcrumbs', $inactive_widgets ) ? 'on' : 'off',
					'default'      => 'on',
					'video_url'    => '#',
					'content_type' => 'custom',
					'feature_type' => 'pro',
					'demo_url'     => self::modules_demo_server() . 'elementor-breadcrumbs-widget/',
				],
			],
			'ultimate_spin_wheel_api' => [
				'form_builder_group' => [
					'input_box'    => [
						[
							'name'        => 'form_builder_email_to',
							'label'       => esc_html__( 'Form Builder Emails Receiver', 'ultimate-spin-wheel' ),
							'placeholder' => esc_html__( 'Email Address', 'ultimate-spin-wheel' ),
							'description' => esc_html__( 'By default, the form builder sends emails to the admin email. If you\'d like to send emails to a different address, you can configure it here.', 'ultimate-spin-wheel' ),
							'type'        => 'input',
							'value'       => ! empty( $saved_api['form_builder_email_to'] ) ? $saved_api['form_builder_email_to'] : null,
						],
					],
					'feature_type' => 'pro',
				],
				'ultimate_spin_wheel_api_google_map_group' => [
					'input_box'    => [
						[
							'name'        => 'google_map_key',
							'label'       => esc_html__( 'Google Map', 'ultimate-spin-wheel' ),
							'placeholder' => esc_html__( 'API Key', 'ultimate-spin-wheel' ),
							'description' => esc_html__( 'Google Maps API is a service that offers detailed maps and other geographic information for use in online and offline map applications, and websites.', 'ultimate-spin-wheel' ),
							'type'        => 'input',
							'value'       => ! empty( $saved_api['google_map_key'] ) ? $saved_api['google_map_key'] : null,
						],
					],
					'feature_type' => 'pro',
				],
				'ultimate_spin_wheel_api_mailchimp_group' => [
					'input_box'    => [
						[
							'name'        => 'mailchimp_api_key',
							'label'       => esc_html__( 'Mailchimp API Key', 'ultimate-spin-wheel' ),
							'placeholder' => esc_html__( 'Access Key', 'ultimate-spin-wheel' ),
							'description' => esc_html__( 'Mailchimp is a popular marketing and automation platform for small businesses.', 'ultimate-spin-wheel' ),
							'type'        => 'input',
							'value'       => ! empty( $saved_api['mailchimp_api_key'] ) ? $saved_api['mailchimp_api_key'] : null,
						],
						[
							'name'        => 'mailchimp_list_id',
							'label'       => esc_html__( 'Audience ID', 'ultimate-spin-wheel' ),
							'placeholder' => esc_html__( 'Audience ID', 'ultimate-spin-wheel' ),
							'description' => esc_html__( 'Each Mailchimp audience has a unique audience ID (sometimes called a list ID) .', 'ultimate-spin-wheel' ),
							'type'        => 'input',
							'value'       => ! empty( $saved_api['mailchimp_list_id'] ) ? $saved_api['mailchimp_list_id'] : null,
						],
					],
					'feature_type' => 'pro',
				],
				'ultimate_spin_wheel_api_instagram_group' => [
					'input_box'    => [
						[

							'name'        => 'instagram_app_id',
							'label'       => esc_html__( 'Instagram', 'ultimate-spin-wheel' ),
							'placeholder' => esc_html__( 'App Id', 'ultimate-spin-wheel' ),
							'description' => '',
							'type'        => 'input',
							'value'       => ! empty( $saved_api['instagram_app_id'] ) ? $saved_api['instagram_app_id'] : null,
						],
						[

							'name'        => 'instagram_app_secret',
							'label'       => esc_html__( 'App Secret', 'ultimate-spin-wheel' ),
							'placeholder' => esc_html__( 'App Secret', 'ultimate-spin-wheel' ),
							'description' => '',
							'type'        => 'input',
							'value'       => ! empty( $saved_api['instagram_app_secret'] ) ? $saved_api['instagram_app_secret'] : null,
						],
						[

							'name'        => 'instagram_access_token',
							'label'       => esc_html__( 'Access Token', 'ultimate-spin-wheel' ),
							'placeholder' => esc_html__( 'Access Token', 'ultimate-spin-wheel' ),
							'description' => '',
							'type'        => 'input',
							'value'       => ! empty( $saved_api['instagram_access_token'] ) ? $saved_api['instagram_access_token'] : null,
						],
					],
					'feature_type' => 'pro',
				],
			],
		];

		self::$widget_list = $widgets_fields['addons'];

		return $widgets_fields;
	}
}
