<?php

namespace USPIN_WHEEL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main class plugin
 */
class Plugin {




	private static $instance;

	/**
	 * @var array
	 */
	private $localize_settings = [];

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since 1.0.0
	 * @return void
	 */

	/**
	 * Modules Manager
	 *
	 * @var Managers
	 */
	private $modules_manager;

	public function __clone() {
		// Cloning instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'ultimate-spin-wheel' ), '1.0.0' );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'ultimate-spin-wheel' ), '1.0.0' );
	}

	/**
	 * @return Plugin -> USPIN_WHEEL
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			/**
			 * Fire this action on the load time
			 * This method will catch by PRO
			 * Pro will not work without this method
			 */
			do_action( 'ultimate_spin_wheel_loaded' );
			self::$instance->add_actions();
		}

		return self::$instance;
	}

	public function getlocalize_settings() {
		return $this->localize_settings;
	}

	/**
	 * App Styles
	 *
	 * @since 1.0.0
	 */
	public function admin_app_styles( $hook_suffix ) {
		if ( 'toplevel_page_ultimate-spin-wheel' !== $hook_suffix && 'ultimate-spin-wheel_page_ultimate-spin-wheel-pro' !== $hook_suffix ) {
			return;
		}
		$direction_suffix = is_rtl() ? '.rtl' : '';
		wp_enqueue_style( 'wp-components' );
		wp_register_style( 'spin-wheel', USPIN_WHEEL_URL . 'build/admin/index.css', [], USPIN_WHEEL_VERSION );
		wp_enqueue_style( 'spin-wheel' );
	}

	public function localize_config() {
		$script_config = [
			'plugin_name'     => defined( 'USPIN_WHEEL_NAME' ) ? USPIN_WHEEL_NAME : '',
			'plugin_slug'     => defined( 'USPIN_WHEEL_SLUG' ) ? USPIN_WHEEL_SLUG : '',
			'admin_url'       => esc_url( admin_url() ),
			'web_url'         => esc_url( home_url() ),
			'rest_url'        => esc_url( get_rest_url() ),
			'ajax_url'        => esc_url( admin_url( 'admin-ajax.php' ) ),
			'version'         => USPIN_WHEEL_VERSION,
			'pro_version'     => defined( 'USPIN_WHEEL_PRO_VERSION' ) ? USPIN_WHEEL_PRO_VERSION : '',
			'nonce'           => wp_create_nonce( 'ultimate_spin_wheel' ),
			'assets_url'      => USPIN_WHEEL_ASSETS_URL,
			'logo'            => USPIN_WHEEL_ASSETS_URL . 'images/logo.png',
			'root_url'        => USPIN_WHEEL_URL,
			'pro_init'        => apply_filters( 'ultimate_spin_wheel_pro_init', false ),
			'current_date'    => wp_date( 'Y-m-d' ),
			'timezone_offset' => get_option( 'gmt_offset' ),
			'current_user' => [
				'domain'       => esc_url( home_url() ),
				'display_name' => wp_get_current_user()->display_name,
				'email'        => wp_get_current_user()->user_email,
				'id'           => wp_get_current_user()->ID,
				'avatar'       => get_avatar_url( wp_get_current_user()->ID ),
			],
		];

		return $script_config;
	}

	public static function get_localize_config() {
		$instance = self::instance();
		return $instance->localize_config();
	}

	/**
	 * App Scripts
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function admin_app_scripts( $hook_suffix ) {

		if ( 'toplevel_page_ultimate-spin-wheel' !== $hook_suffix ) {
			return;
		}

		$asset_file = plugin_dir_path( __FILE__ ) . 'build/admin/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = include $asset_file;
		wp_enqueue_script( 'wp-core-data' );
		wp_enqueue_script( 'wp-element' );
		wp_enqueue_script( 'wp-components' );
		wp_enqueue_script( 'wp-api-fetch' );
		wp_enqueue_script( 'wp-i18n' );

		wp_register_script( 'spin-wheel', USPIN_WHEEL_URL . 'build/admin/index.js', [], $asset['version'], true );
		wp_enqueue_script( 'spin-wheel' );

		/**
		 * Localize Script
		 */
		$script_config = $this->localize_config();

		wp_localize_script( 'spin-wheel', 'USPIN_CONFIG_ADMIN', $script_config );
	}

	/**
	 * Edit Config / Settings Scripts
	 *
	 * @since 1.5.0
	 * @return void
	 */
	public function edit_config_scripts( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, [ 'post.php', 'post-new.php' ] ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'wowdevs_engage' !== $screen->post_type ) {
			return;
		}

		$asset_file = plugin_dir_path( __FILE__ ) . 'build/campaign/index.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = include $asset_file;

		// Enqueue WordPress dependencies
		wp_enqueue_style( 'wp-components' );
		wp_enqueue_script( 'wp-core-data' );
		wp_enqueue_script( 'wp-element' );
		wp_enqueue_script( 'wp-components' );
		wp_enqueue_script( 'wp-api-fetch' );
		wp_enqueue_script( 'wp-i18n' );

		// Enqueue WordPress Media Library for image uploads
		wp_enqueue_media();

		// Register and enqueue our config assets
		$version = ( isset( $asset['version'] ) ) ? $asset['version'] : USPIN_WHEEL_VERSION;
		wp_register_style( 'uspw-config-style', USPIN_WHEEL_URL . 'build/campaign/index.css', [], $version );
		wp_enqueue_style( 'uspw-config-style' );

		wp_register_script( 'spin-wheel-config', USPIN_WHEEL_URL . 'build/campaign/index.js', $asset['dependencies'], $asset['version'], true );
		wp_enqueue_script( 'spin-wheel-config' );

		/**
		 * Localize Script
		 */
		$script_config = $this->localize_config();

		wp_localize_script( 'spin-wheel-config', 'USPIN_CONFIG_ADMIN', $script_config );
	}

	public function add_config_app_container() {
		$screen = get_current_screen();
		if ( 'wowdevs_engage' === $screen->post_type ) {
			echo '<div id="uspw-config-app" class="ultimate-spin-wheel"></div>';
		}
	}

	public function add_admin_body_class( $classes ) {
		$screen = get_current_screen();
		if ( $screen && 'wowdevs_engage' === $screen->post_type && in_array( $screen->base, [ 'post', 'add' ] ) ) {
			$classes .= ' ultimate-spin-wheel-body ';
		}
		return $classes;
	}

	public function remove_unnecessary_meta_boxes() {
		$screen = get_current_screen();
		if ( $screen && 'wowdevs_engage' === $screen->post_type ) {
			// Remove standard meta boxes to provide a SaaS-like experience
			// We keep 'submitdiv' (Publish panel) as requested for the right column
			remove_meta_box( 'slugdiv', 'wowdevs_engage', 'normal' );
			remove_meta_box( 'postcustom', 'wowdevs_engage', 'normal' );
			remove_meta_box( 'commentstatusdiv', 'wowdevs_engage', 'normal' );
			remove_meta_box( 'commentsdiv', 'wowdevs_engage', 'normal' );
			remove_meta_box( 'authordiv', 'wowdevs_engage', 'normal' );
			remove_meta_box( 'revisionsdiv', 'wowdevs_engage', 'normal' );
			remove_meta_box( 'submitdiv', 'wowdevs_engage', 'side' );

			// Remove ACF if present
			remove_meta_box( 'acf-form', 'wowdevs_engage', 'normal' );
		}
	}

	public function force_two_columns( $columns ) {
		$screen = get_current_screen();
		if ( $screen && 'wowdevs_engage' === $screen->post_type ) {
			return 2;
		}
		return $columns;
	}

	protected function add_actions() {
		// Admin-specific actions
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_app_styles' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_app_scripts' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'edit_config_scripts' ] );
		add_action( 'edit_form_after_title', [ $this, 'add_config_app_container' ] );
		add_filter( 'admin_body_class', [ $this, 'add_admin_body_class' ] );
		add_action( 'add_meta_boxes', [ $this, 'remove_unnecessary_meta_boxes' ], 999 );
		add_filter( 'screen_layout_columns', [ $this, 'force_two_columns' ] );
	}

	/**
	 * Plugin-> USPIN_WHEEL constructor.
	 */
	private function __construct() {
	}
}
