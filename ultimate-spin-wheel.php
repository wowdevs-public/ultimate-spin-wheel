<?php
/**
 * Plugin Name:       Ultimate Spin Wheel - Gamify Your Store & Boost Sales
 * Plugin URI:        https://wowdevs.com/plugins/ultimate-spin-wheel
 * Description:       The Ultimate Spin Wheel plugin allows you to engage your visitors with an interactive cart that offers coupons and other rewards, seamlessly integrated with WooCommerce.
 * Version:           1.0.4
 * Requires at least: 6.1
 * Requires PHP:      7.4
 * Author:            wowDevs
 * Author URI:        https://wowdevs.com/
 * License:           GPL-2.0-or-later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ultimate-spin-wheel
 * Domain Path:       /languages
 *
 * @package           USPIN_WHEEL
 * @author            wowDevs
 * @copyright         2024 wowDevs
 * @license           GPL-2.0-or-later
 */

/**
 * Prevent direct access
 */
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

define( 'USPIN_WHEEL_VERSION', '1.0.4' );

define( 'USPIN_WHEEL_NAME', 'Spin Wheel' );
define( 'USPIN_WHEEL_SLUG', 'ultimate-spin-wheel' );

define( 'USPIN_WHEEL__FILE__', __FILE__ );
define( 'USPIN_WHEEL_PATH', plugin_dir_path( USPIN_WHEEL__FILE__ ) );
define( 'USPIN_WHEEL_INCLUDES', USPIN_WHEEL_PATH . 'includes/' );
define( 'USPIN_WHEEL_MODULES_PATH', USPIN_WHEEL_PATH . 'modules/' );
define( 'USPIN_WHEEL_URL', plugins_url( '/', USPIN_WHEEL__FILE__ ) );
define( 'USPIN_WHEEL_PATH_NAME', basename( dirname( USPIN_WHEEL__FILE__ ) ) );
define( 'USPIN_WHEEL_INC_PATH', USPIN_WHEEL_PATH . 'includes/' );
define( 'USPIN_WHEEL_ASSETS_URL', USPIN_WHEEL_URL . 'assets/' );
define( 'USPIN_WHEEL_ASSETS_PATH', USPIN_WHEEL_PATH . 'assets/' );
define( 'USPIN_WHEEL_ASSETS_URL_ADMIN', USPIN_WHEEL_URL . 'assets/admin/' );

/**
 * Is Pro Activated
 */
if ( ! function_exists( 'ultimate_spin_wheel_pro_is_activated' ) ) {
	function ultimate_spin_wheel_pro_is_activated() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$file_path = 'ultimate-spin-wheel-pro/ultimate-spin-wheel-pro.php';

		if ( is_plugin_active( $file_path ) ) {
			return true;
		}

		return false;
	}
}

/**
 * Installer
 *
 * @since 1.0.0
 */
require_once USPIN_WHEEL_INCLUDES . 'class-installer.php';

function ultimate_spin_wheel_load_plugin() {
	require_once USPIN_WHEEL_PATH . 'class-core.php';
	require_once USPIN_WHEEL_PATH . 'plugin.php';
	\USPIN_WHEEL\Core::instance();
	\USPIN_WHEEL\Plugin::instance();
}

add_action( 'init', 'ultimate_spin_wheel_load_plugin' );

function ultimate_spin_wheel_activate() {
	$installer = new \USPIN_WHEEL\Installer();
	$installer->run();
}

register_activation_hook( __FILE__, 'ultimate_spin_wheel_activate' );

/**
 * Show notice in WP Dashboard if WooCommerce is not loaded.
 *
 * @since 1.0.0
 *
 * @return void
 */
function ultimate_spin_wheel_fail_load() {
	$screen = get_current_screen();
	if ( isset( $screen->parent_file ) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id ) {
		return;
	}

	$plugin = 'woocommerce/woocommerce.php';

	if ( ultimate_spin_wheel_is_woocommerce_installed() ) {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$activation_url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin );

		$message  = '<p>' . esc_html__( 'Ultimate Spin Wheel is not working because you need to activate the WooCommerce plugin.', 'ultimate-spin-wheel' ) . '</p>';
		$message .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $activation_url, esc_html__( 'Activate WooCommerce Now', 'ultimate-spin-wheel' ) ) . '</p>';
	} else {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		$install_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=woocommerce' ), 'install-plugin_woocommerce' );

		$message  = '<p>' . esc_html__( 'Ultimate Spin Wheel is not working because you need to install the WooCommerce plugin.', 'ultimate-spin-wheel' ) . '</p>';
		$message .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $install_url, esc_html__( 'Install WooCommerce Now', 'ultimate-spin-wheel' ) ) . '</p>';
	}

	printf( '<div class="error"><p>%s</p></div>', wp_kses_post( $message ) );
}

/**
 * Check if WooCommerce is installed
 *
 * @since 1.0.0
 *
 * @return bool
 */
if ( ! function_exists( 'ultimate_spin_wheel_is_woocommerce_installed' ) ) {
	function ultimate_spin_wheel_is_woocommerce_installed() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();

		if ( array_key_exists( 'woocommerce/woocommerce.php', $plugins ) ) {
			return true;
		}

		return false;
	}
}

/**
 * SDK Integration
 */

if ( ! function_exists( 'ultimate_spin_wheel_dci_plugin' ) ) {
	function ultimate_spin_wheel_dci_plugin() {

		// Include DCI SDK.
		require_once __DIR__ . '/dci/start.php';

		wp_register_style( 'dci-sdk-ultimate-spin-wheel', USPIN_WHEEL_URL . 'dci/assets/css/dci.css', array(), '1.3.0', 'all' );
		wp_enqueue_style( 'dci-sdk-ultimate-spin-wheel' );

		dci_dynamic_init(
			array(
				'sdk_version'          => '1.2.1',
				'product_id'           => 6,
				'plugin_name'          => 'Ultimate Spin Wheel', // make simple, must not empty
				'plugin_title'         => 'Love using Ultimate Spin Wheel? Congrats 🎉  ( Never miss an Important Update )', // You can describe your plugin title here
				'plugin_icon'          => USPIN_WHEEL_ASSETS_URL . 'images/logo.png', // delete the line of you don't need
				'api_endpoint'         => 'https://dashboard.wowdevs.com/wp-json/dci/v1/data-insights',
				'slug'                 => 'ultimate-spin-wheel',
				'core_file'            => false,
				'plugin_deactivate_id' => false,
				'menu'                 => array(
					'slug' => 'ultimate-spin-wheel',
				),
				'public_key'           => 'pk_ThVNbRDOgEuRd9JkQK1YGgmaDnXQAlkf',
				'is_premium'           => false,
				'popup_notice'         => false,
				'deactivate_feedback'  => true,
				// 'delay_time'           => array(
				// 'time' => 3 * DAY_IN_SECONDS,
				// ),
				'text_domain'          => 'ultimate-spin-wheel',
				'plugin_msg'           => '<p>Be Top-contributor by sharing non-sensitive plugin data and create an impact to the global WordPress community today! You can receive valuable emails periodically.</p>',
			)
		);
	}
	add_action( 'admin_init', 'ultimate_spin_wheel_dci_plugin' );
}


/**
 * Review Automation Integration
 */

if ( ! function_exists( 'ultimate_spin_wheel_rc_plugin' ) ) {
	function ultimate_spin_wheel_rc_plugin() {

		require_once USPIN_WHEEL_PATH . 'includes/feedbacks/start.php';

		wp_register_style( 'rc-sdk-ultimate-spin-wheel', USPIN_WHEEL_URL . 'includes/feedbacks/assets/rc.css', array(), '1.0.0', 'all' );
		wp_enqueue_style( 'rc-sdk-ultimate-spin-wheel' );

		rc_dynamic_init(
			array(
				'sdk_version'  => '1.0.0',
				'plugin_name'  => 'Ultimate Spin Wheel',
				'plugin_icon'  => USPIN_WHEEL_ASSETS_URL . 'images/logo.png',
				'slug'         => 'ultimate-spin-wheel',
				'menu'         => array(
					'slug' => 'ultimate-spin-wheel',
				),
				'review_url'   => 'https://wordpress.org/support/plugin/ultimate-spin-wheel/reviews/#new-post',
				'plugin_title' => 'Yay! Great that you\'re using Ultimate Spin Wheel',
				'plugin_msg'   => '<p>Loved using Ultimate Spin Wheel on your website? Share your experience in a review and help us spread the love to everyone right now. Good words will help the community.</p>',
			)
		);
	}
	add_action( 'admin_init', 'ultimate_spin_wheel_rc_plugin' );
}
