<?php
/**
 * Plugin Name: ezDeFi - Bitcoin, Ethereum and Cryptocurrencies Payment Gateway for Easy Digital Downloads
 * Plugin URI: https://ezdefi.io/
 * Description: Accept Bitcoin, Ethereum and Cryptocurrencies on your Easy Digital Downloads store with ezDeFi
 * Version: 1.0.0
 * Author: ezDeFi
 * Author URI: https://ezdefi.io/
 * License: GPLv2 or later
 * Text Domain: edd-ezdefi
 * Domain Path: /languages
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

		register_activation_hook( __FILE__, array( $this, 'activate' ) );

		$this->define_constants();
	}

	/**
	 * Initializes the plugin.
	 */
	public function init_plugin()
	{
		require_once plugin_dir_path( __FILE__ ) . 'class-edd-ezdefi.php';

		require_once plugin_dir_path( __FILE__ ) . 'functions.php';

		load_plugin_textdomain( 'edd-ezdefi', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

		edd_ezdefi();
	}

	/**
	 * Run when activate plugin
	 */
	public function activate()
	{
		global $wpdb;

		$sql = array();

		$charset_collate = $wpdb->get_charset_collate();

		$exception_table_name = $wpdb->prefix . 'edd_ezdefi_exception';

		$sql[] = "CREATE TABLE $exception_table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			amount_id decimal(60,30) NOT NULL,
			currency varchar(10) NOT NULL,
			order_id int(11),
			status varchar(20),
			payment_method varchar(100),
			explorer_url varchar(200),
			PRIMARY KEY (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		$wpdb->query( "
			CREATE EVENT IF NOT EXISTS `edd_ezdefi_clear_exception_table`
			ON SCHEDULE EVERY 7 DAY
			DO
				DELETE FROM $exception_table_name;
		" );
	}

	/**
	 * Define constants
	 */
	public function define_constants()
	{
		define( 'EDD_EZDEFI_VERSION', '1.0.0' );
		define( 'EDD_EZDEFI_MAIN_FILE', __FILE__ );
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