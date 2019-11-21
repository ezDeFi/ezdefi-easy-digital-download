<?php
/**
 * Plugin Name: EZDefi for Easy Digital Downloads
 * Description: EZDefi Gateway integration for Easy Digital Downloads
 * Version: 1.0
 * Author: Nexty Platform
 * Author URI: https://nexty.io
 * License: GPL
 * Text Domain: edd-ezdefi
 */

defined( 'ABSPATH' ) or exit;

if( ! EDD_Ezdefi_Loader::is_edd_active() ) {
	return;
}

class EDD_Ezdefi_Loader
{
	protected static $instance;

	protected $notices = array();

	/**
	 * Check if EDD is active
	 */
	public static function is_edd_active()
	{
		$active_plugins = (array) get_option( 'active_plugins', array() );
		if( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
		return in_array( 'easy-digital-downloads/easy-digital-downloads.php', $active_plugins ) || array_key_exists( 'easy-digital-downloads/easy-digital-downloads.php', $active_plugins );
	}

	/**
	 * EDD_Ezdefi_Loader constructor.
	 */
	protected function __construct()
	{
		add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
	}

	/**
	 * Initializes the plugin.
	 */
	public function init_plugin()
	{
		require_once plugin_dir_path( __FILE__ ) . 'class-edd-ezdefi.php';

		require_once plugin_dir_path( __FILE__ ) . 'functions.php';

		edd_ezdefi();
	}

	/**
	 * Get EDD_Ezdefi_Loader instance
	 */
	public static function instance()
	{
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

EDD_Ezdefi_Loader::instance();