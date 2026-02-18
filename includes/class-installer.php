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
			`phone` VARCHAR(255) NULL DEFAULT NULL,
			`campaign_type` VARCHAR(255) NULL DEFAULT NULL,
			`campaign_id` BIGINT(20) NULL DEFAULT NULL,
			`campaign_title` VARCHAR(255) NULL DEFAULT NULL,
			`others_data` LONGTEXT NULL DEFAULT NULL COMMENT 'JSON DATA',
			`user_data` LONGTEXT NULL DEFAULT NULL COMMENT 'JSON DATA',
			`optin` VARCHAR(1) NULL DEFAULT NULL COMMENT 'Y = Yes, N = No',
			`status` LONGTEXT NULL DEFAULT NULL COMMENT 'JSON DATA: stage, updated_at, history',
			`integration_logs` LONGTEXT NULL DEFAULT NULL COMMENT 'JSON DATA',
			`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`)
		) $charset_collate";

		/**
		 * If phone column not exist then add it
		 *
		 * @since 1.0.5
		 */

		//phpcs:ignore
		$column = $wpdb->get_results("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '{$wpdb->prefix}wdengage_entries' AND COLUMN_NAME = 'phone'");

		if ( empty( $column ) ) {
			$wpdb->query( "ALTER TABLE `{$wpdb->prefix}wdengage_entries` ADD `phone` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Empty Email for Logs' AFTER `email`;" );
		}

		/**
		 * Update status column to LONGTEXT if it's currently VARCHAR
		 */
		if ( ! empty( $status_column ) && strtolower( $status_column[0]->DATA_TYPE ) !== 'longtext' ) {
			$wpdb->query( "ALTER TABLE `{$wpdb->prefix}wdengage_entries` MODIFY `status` LONGTEXT DEFAULT NULL" );
		}

		$integration_column = $wpdb->get_results( "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . DB_NAME . "' AND TABLE_NAME = '{$wpdb->prefix}wdengage_entries' AND COLUMN_NAME = 'integration_logs'" );

		if ( empty( $integration_column ) ) {
			$wpdb->query( "ALTER TABLE `{$wpdb->prefix}wdengage_entries` ADD `integration_logs` LONGTEXT NULL DEFAULT NULL COMMENT 'JSON DATA' AFTER `status`;" );
		}

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		dbDelta( $schema );
	}
}
