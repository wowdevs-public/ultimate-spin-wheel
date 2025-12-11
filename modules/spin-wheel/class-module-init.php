<?php

namespace USPIN_WHEEL\Modules\SpinWheel;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use USPIN_WHEEL\Modules\SpinWheel\Spin_Wheel;
use USPIN_WHEEL\Modules\SpinWheel\Reports;
use USPIN_WHEEL\Modules\SpinWheel\Entries;

class Module_Init {
	private static $instance = null;

	public function __construct() {
		$this->registered_post_type();
		Spin_Wheel::instance();
		Reports::instance();
		Entries::instance();
	}

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function get_info() {
		return [
			'title'              => esc_html__( 'Spin Wheel', 'ultimate-spin-wheel' ),
			'required'           => true,
			'default_activation' => true,
			'has_style'          => true,
		];
	}
	public function registered_post_type() {
		$labels = [
			'name'               => __( 'Engagements', 'ultimate-spin-wheel' ),
			'singular_name'      => __( 'Engagement', 'ultimate-spin-wheel' ),
			'menu_name'          => __( 'Engagements', 'ultimate-spin-wheel' ),
			'name_admin_bar'     => __( 'Engagements', 'ultimate-spin-wheel' ),
			'add_new'            => __( 'Add New', 'ultimate-spin-wheel' ),
			'add_new_item'       => __( 'Add New Template', 'ultimate-spin-wheel' ),
			'new_item'           => __( 'New Template', 'ultimate-spin-wheel' ),
			'edit_item'          => __( 'Edit Template', 'ultimate-spin-wheel' ),
			'view_item'          => __( 'View Template', 'ultimate-spin-wheel' ),
			'all_items'          => __( 'All Templates', 'ultimate-spin-wheel' ),
			'search_items'       => __( 'Search Templates', 'ultimate-spin-wheel' ),
			'parent_item_colon'  => __( 'Parent Template:', 'ultimate-spin-wheel' ),
			'not_found'          => __( 'No found.', 'ultimate-spin-wheel' ),
			'not_found_in_trash' => __( 'No found in Trash.', 'ultimate-spin-wheel' ),
		];

		$args = [
			'labels'              => $labels,
			'description'         => __( 'Description.', 'ultimate-spin-wheel' ),
			'taxonomies'          => [],
			'hierarchical'        => false,
			'public'              => true,
			'show_in_menu'        => true,
			'show_ui'             => true,
			'show_in_admin_bar'   => false,
			'menu_position'       => null,
			'menu_icon'           => null,
			'publicly_queryable'  => true,
			'supports'            => [ 'title', 'custom-fields' ],
			'exclude_from_search' => true,
			'has_archive'         => false,
			'query_var'           => true,
			'can_export'          => true,
			'rewrite'             => [ 'slug' => 'wowdevs-engage' ],
			'show_in_nav_menus'   => false,
			'capability_type'     => 'post',
			'show_in_rest'        => true,
			'template_lock'       => 'all',
		];

		register_post_type( 'wowdevs_engage', $args );

		// Register all meta fields properly
		$string_meta_fields = [
			'uspw_type',
			'uspw_status',
			'uspw_start_date',
			'uspw_end_date',
			'uspw_popup_settings',
		];

		foreach ( $string_meta_fields as $meta_field ) {
			register_post_meta('wowdevs_engage', $meta_field, array(
				'show_in_rest' => true,
				'single'       => true,
				'type'         => 'string',
				'default'      => '',
			));
		}

		// Register coupons meta field separately (excluded from REST API)
		register_post_meta('wowdevs_engage', 'uspw_coupons', array(
			'show_in_rest' => true, // Explicitly exclude from REST API
			'single'       => true,
			'type'         => 'string',
			'default'      => '',
			'permission'   => function() {
				// permission to edit
				return current_user_can( 'edit_posts' );
			},
		));

		register_post_meta('wowdevs_engage', 'uspw_scroll_percentage', [
			'type'         => 'integer', // Important: use 'integer' not 'string'
			'single'       => true,
			'default'      => 50,
			'show_in_rest' => true,
		]);

		register_post_meta('wowdevs_engage', 'uspw_time_delay', [
			'type'         => 'integer',
			'single'       => true,
			'default'      => 5,
			'show_in_rest' => true,
		]);

		register_post_meta('wowdevs_engage', 'uspw_max_impressions', [
			'type'         => 'integer',
			'single'       => true,
			'default'      => 0,
			'show_in_rest' => true,
		]);

		register_post_meta('wowdevs_engage', 'uspw_impressions_count', [
			'type'         => 'integer',
			'single'       => true,
			'default'      => 0,
			'show_in_rest' => true,
		]);

		register_post_meta('wowdevs_engage', 'uspw_campaign_priority', [
			'type'         => 'integer',
			'single'       => true,
			'default'      => 1,
			'show_in_rest' => true,
		]);

		register_post_meta('wowdevs_engage', 'uspw_custom_days', [
			'type'         => 'integer',
			'single'       => true,
			'default'      => 7,
			'show_in_rest' => true,
		]);

		register_post_meta('wowdevs_engage', 'uspw_count_impressions', [
			'type'         => 'integer',
			'single'       => true,
			'default'      => 7,
			'show_in_rest' => true,
		]);

		// Register array-based meta fields (for display options)
		$array_meta_fields = [
			'uspw_display_on',
			'uspw_not_display_on',
			'uspw_display_special_pages',
			'uspw_not_display_special_pages',
			'uspw_display_custom_pages',
			'uspw_not_display_custom_pages',
			'uspw_display_roles',
			// New campaign/popup meta fields (array values)
			'uspw_campaign_triggers',
			'uspw_referrer_domains',
			'uspw_target_devices',
			'uspw_custom_designs',
		];

		foreach ( $array_meta_fields as $meta_field ) {
			register_post_meta('wowdevs_engage', $meta_field, array(
				'show_in_rest' => array(
					'schema' => array(
						'type' => 'string', // Store as JSON string
					),
				),
				'single'       => true,
				'type'         => 'string',
				'default'      => '[]',
			));
		}
	}
}
