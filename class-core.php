<?php
/**
 * Core File
 *
 * @package USPIN_WHEEL
 * @since 3.0.0
 */

namespace USPIN_WHEEL;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin Core
 * Register Files / Layouts
 *
 * @since 3.0.0
 * @author Shahidul Islam
 */
final class Core {

	/**
	 * Instance
	 *
	 * @var object
	 * @since 3.0.0
	 */
	private static $instance;

	/**
	 * Instance
	 *
	 * @return object
	 * @since 3.0.0
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->init();

			do_action( 'ultimate_spin_wheel_loaded' );
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @return void
	 * @since 3.0.0
	 */
	public function __construct() {
	}

	/**
	 * Init
	 *
	 * @return void
	 * @since 3.0.0
	 */
	public function init() {
		$this->include_files();
	}

	/**
	 * Include Files
	 *
	 * @return void
	 * @since 3.0.0
	 */
	public function include_files() {
		/**
		 * Admin Files with REST API
		 *
		 * No admin Check, Because it's required also for REST API
		 */
		if ( is_admin() ) {
			require_once USPIN_WHEEL_INC_PATH . 'addons.php';

			require_once USPIN_WHEEL_INC_PATH . 'admin/class-menu.php';
			require_once USPIN_WHEEL_INC_PATH . 'admin/class-admin.php';
			new Admin();
		}

		require_once USPIN_WHEEL_MODULES_PATH . '/spin-wheel/class-module-init.php';
		require_once USPIN_WHEEL_MODULES_PATH . '/spin-wheel/class-spin-wheel.php';
		require_once USPIN_WHEEL_MODULES_PATH . '/spin-wheel/class-reports.php';
		require_once USPIN_WHEEL_MODULES_PATH . '/spin-wheel/class-entries.php';
		$spin_wheel = new \USPIN_WHEEL\Modules\SpinWheel\Module_Init();
		$spin_wheel->get_instance();

		/**
		 * Admin Feeds
		 */
		if ( is_admin() ) {
			require_once USPIN_WHEEL_INC_PATH . 'class-admin-feeds.php';
		}
	}
}
