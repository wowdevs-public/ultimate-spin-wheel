<?php
/**
 * Admin class
 *
 * @package USPIN_WHEEL
 * @since 1.0.0
 */

namespace USPIN_WHEEL;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Description of Menu
 *
 * @since 1.0.0
 */

class Admin {
	public function __construct() {
		$this->dispatch_actions();
		new Admin\Menu();
	}

	/**
	 * Dispatch Actions
	 *
	 * @since 1.0.0
	 */
	public function dispatch_actions() {
		// coming soon
	}
}
