<?php
/**
 * Plugin Name: EZPay for Easy Digital Downloads
 * Description: EZPay Gateway integration for Easy Digital Downloads
 * Version: 1.0
 * Author: Nexty Platform
 * Author URI: https://nexty.io
 * License: GPL
 * Text Domain: edd-ezpay
 */

defined( 'ABSPATH' ) or exit;

if( ! EDD_Ezpay_Loader::is_edd_active() ) {
	return;
}

class EDD_Ezpay_Loader
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
	 * EDD_Ezpay_Loader constructor.
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
		require_once plugin_dir_path( __FILE__ ) . 'class-edd-ezpay.php';

		require_once plugin_dir_path( __FILE__ ) . 'functions.php';

		edd_ezpay();
	}

	/**
	 * Get EDD_Ezpay_Loader instance
	 */
	public static function instance()
	{
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

EDD_Ezpay_Loader::instance();

//if( ! defined( 'EDD_EZPAY_FILE' ) ) {
//    define( 'EDD_EZPAY_FILE', __FILE__ );
//}
//
//if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
//    return;
//}
//
//if( ! class_exists( 'EDD_EZPay' ) ) {
//    include_once dirname( __FILE__ ) . '/includes/class-edd-ezpay.php';
//}
//
//function EDD_EZPay() {
//    $instance = EDD_EZPay_Class::instance();
//    $instance->init();
//
//    return $instance;
//}
//
///** Load plugin after Easy Digital Downloads */
//add_action( 'plugin_loaded', 'EDD_EZPay' );