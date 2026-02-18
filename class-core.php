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

		require_once USPIN_WHEEL_INC_PATH . 'core/class-post-type.php';
		require_once USPIN_WHEEL_INC_PATH . 'core/class-style-generator.php';
		require_once USPIN_WHEEL_INC_PATH . 'core/class-spin-wheel.php';
		require_once USPIN_WHEEL_INC_PATH . 'core/class-reports.php';
		require_once USPIN_WHEEL_INC_PATH . 'core/class-entries.php';
		require_once USPIN_WHEEL_INC_PATH . 'core/class-settings.php';
		require_once USPIN_WHEEL_INC_PATH . 'core/class-campaigns.php';

		// AI Chat and Integrations are PRO features
		// Load from pro plugin if active and files exist
		$pro_integrations_path = defined( 'USPW_PRO_INTEGRATIONS_PATH' ) ? USPW_PRO_INTEGRATIONS_PATH : '';

		if ( $pro_integrations_path ) {
			if ( file_exists( $pro_integrations_path . 'class-ai-chat.php' ) ) {
				require_once $pro_integrations_path . 'class-ai-chat.php';
			}
			if ( file_exists( $pro_integrations_path . 'class-integrations.php' ) ) {
				require_once $pro_integrations_path . 'class-integrations.php';
			}
		}

		\USPIN_WHEEL\Includes\Core\Settings::instance();
		\USPIN_WHEEL\Includes\Core\Spin_Wheel::instance();

		// Initialize admin handlers
		if ( is_admin() ) {
			\USPIN_WHEEL\Includes\Core\Reports::instance();
			\USPIN_WHEEL\Includes\Core\Entries::instance();
			\USPIN_WHEEL\Includes\Core\Campaigns::instance();
		}

		$spin_wheel = new \USPIN_WHEEL\Includes\Core\Post_Type();
		$spin_wheel->get_instance();

		/**
		 * Admin Feeds
		 */
		if ( is_admin() ) {
			require_once USPIN_WHEEL_INC_PATH . 'class-admin-feeds.php';
		}
	}
}
