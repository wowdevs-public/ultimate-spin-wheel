<?php
/**
 * Menu class
 *
 * @package USPIN_WHEEL\Admin
 * @since 1.0.0
 */

namespace USPIN_WHEEL\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Description of Menu
 *
 * @since 1.0.0
 */
class Menu {
	/**
	 * Constructor
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		add_filter( 'plugin_action_links_' . plugin_basename( USPIN_WHEEL__FILE__ ), [ $this, 'plugin_action_links' ] );
	}

	/**
	 * Register admin menu
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function admin_menu() {
		$parent_slug = 'ultimate-spin-wheel';
		$capability  = 'manage_options';
		add_menu_page( esc_html__( 'Spin Wheel', 'ultimate-spin-wheel' ), esc_html__( 'Spin Wheel', 'ultimate-spin-wheel' ), $capability, $parent_slug, [ $this, 'plugin_layout' ], $this->get_b64_icon(), 59 );

		add_submenu_page( $parent_slug, esc_html__( 'Dashboard', 'ultimate-spin-wheel' ), esc_html__( 'Dashboard', 'ultimate-spin-wheel' ), $capability, $parent_slug, [
			$this,
			'plugin_layout',
		] );

		add_submenu_page( $parent_slug, esc_html__( 'Campaigns', 'ultimate-spin-wheel' ), esc_html__( 'Campaigns', 'ultimate-spin-wheel' ), $capability, $parent_slug . '#campaigns', [
			$this,
			'plugin_layout',
		] );

		add_submenu_page( $parent_slug, esc_html__( 'Leads', 'ultimate-spin-wheel' ), esc_html__( 'Leads', 'ultimate-spin-wheel' ), $capability, $parent_slug . '#entries', [
			$this,
			'plugin_layout',
		] );

		add_submenu_page( $parent_slug, esc_html__( 'Settings', 'ultimate-spin-wheel' ), esc_html__( 'Settings', 'ultimate-spin-wheel' ), $capability, $parent_slug . '#settings', [
			$this,
			'plugin_layout',
		] );

		if ( ! ultimate_spin_wheel_pro_is_activated() ) {
			add_submenu_page(
				$parent_slug,
				esc_html__( 'Get PRO', 'ultimate-spin-wheel' ),
				'<span style="color: #ffd700; font-weight: 600;">👑 ' . esc_html__( 'Get PRO', 'ultimate-spin-wheel' ) . '</span>',
				$capability,
				$parent_slug . '#license',
				[
					$this,
					'plugin_layout',
				]
			);
		}

		if ( ultimate_spin_wheel_pro_is_activated() ) {
			add_submenu_page( $parent_slug, esc_html__( 'License', 'ultimate-spin-wheel' ), esc_html__( 'License', 'ultimate-spin-wheel' ), $capability, $parent_slug . '#license', [
				$this,
				'plugin_layout',
			] );
		}

		add_submenu_page( $parent_slug, esc_html__( 'Support', 'ultimate-spin-wheel' ), esc_html__( 'Support', 'ultimate-spin-wheel' ), $capability, $parent_slug . '#support', [
			$this,
			'plugin_layout',
		] );
	}

	/**
	 * Add action links on plugins page
	 *
	 * @param array $links Existing plugin action links.
	 * @return array Modified plugin action links.
	 * @since 1.0.0
	 */
	public function plugin_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'admin.php?page=ultimate-spin-wheel' ),
			esc_html__( 'Settings', 'ultimate-spin-wheel' )
		);

		// Add Settings link at the beginning
		array_unshift( $links, $settings_link );

		// Add Get Pro link with 30% OFF promotion only if pro is not activated
		if ( ! ultimate_spin_wheel_pro_is_activated() ) {
			$pro_link = sprintf(
				'<a href="%s" style="color: #ce1b7dff; font-weight: 700; text-decoration: none;">%s</a>',
				admin_url( 'admin.php?page=ultimate-spin-wheel#license' ),
				'👑 ' . esc_html__( 'Get PRO', 'ultimate-spin-wheel' ) . ' ✨ ' . esc_html__( 'Save 30%', 'ultimate-spin-wheel' )
			);
			array_unshift( $links, $pro_link );
		}

		return $links;
	}

	/**
	 * Plugin Layout
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function plugin_layout() {
		echo '<div id="ultimate-spin-wheel" class="wrap ultimate-spin-wheel"> <h2>Loading...</h2> </div>';
	}

	/**
	 *
	 * Get the base64 encoded icon for the menu
	 *
	 * @return string
	 */
	public static function get_b64_icon() {
		return 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( USPIN_WHEEL_ASSETS_PATH . 'images/logo-menu.svg' ) );
	}
}
