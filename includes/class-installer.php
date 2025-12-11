<?php
/**
 * Plugin Installer
 *
 * @package   Ultimate Spin Wheel
 */

namespace USPIN_WHEEL;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Installer class
 */
class Installer {
	/**
	 * Runt the installer
	 *
	 * @return void
	 */
	public function run() {
		$this->add_version();
		$this->create_tables();
	}

	public function add_version() {
		$installed = get_option( 'ultimate_spin_wheel_installed', false );

		if ( ! $installed ) {
			update_option( 'ultimate_spin_wheel_installed', time() );
		}

		update_option( 'ultimate_spin_wheel_version', USPIN_WHEEL_VERSION );
	}

	/**
	 * Create nessary database tables
	 *
	 * @return void
	 */
	public function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$schema = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}wdengage_entries` (
			`id` INT(11) NOT NULL AUTO_INCREMENT,
			`name` VARCHAR(255) DEFAULT NULL,
			`email` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Empty Email for Logs',
      `campaign_type` VARCHAR(255) NULL DEFAULT NULL,
      `campaign_id` BIGINT(20) NULL DEFAULT NULL,
      `campaign_title` VARCHAR(255) NULL DEFAULT NULL,
      `others_data` LONGTEXT NULL DEFAULT NULL COMMENT 'JSON DATA',
      `user_data` LONGTEXT NULL DEFAULT NULL COMMENT 'JSON DATA',
			`optin` VARCHAR(1) NULL DEFAULT NULL COMMENT 'Y = Yes, N = No',
      `status` VARCHAR(255) NULL DEFAULT NULL,
			`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`)
		) $charset_collate";

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		dbDelta( $schema );
	}
}
