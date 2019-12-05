<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezdefi
{
	protected static $instance;

	/** EDD_Ezdefi constructor */
	protected function __construct()
	{
		$this->define_constants();

		$this->includes();

		add_filter( 'edd_payment_gateways', array( $this, 'register_gateway' ) );

		add_action( 'init', array( $this, 'listen_for_action' ), 1, 1 );
	}

	/** Define constants */
	public function define_constants()
	{
	    define( 'EDD_EZDEFI_FILE', __FILE__ );
	}

	/** Includes required files */
	public function includes()
	{
		require_once( $this->plugin_path() . '/includes/class-edd-ezdefi-db.php' );
		require_once( $this->plugin_path() . '/includes/class-edd-ezdefi-api.php' );
		require_once( $this->plugin_path() . '/includes/class-edd-ezdefi-ajax.php' );
		require_once( $this->plugin_path() . '/includes/class-edd-ezdefi-frontend.php' );
		require_once( $this->plugin_path() . '/includes/class-edd-ezdefi-payment.php' );
		require_once( $this->plugin_path() . '/includes/class-edd-ezdefi-shortcode.php' );

		if( defined( 'DOING_CRON' ) || is_admin() ) {
			require_once( $this->plugin_path() . '/includes/admin/class-edd-ezdefi-admin.php' );
		}
	}

	/** Get the plugin url */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', EDD_EZDEFI_FILE ) );
	}

	/** Get the plugin path */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( EDD_EZDEFI_FILE ) );
	}

	/** Register Ezdefi gateway */
	public function register_gateway( $gateways )
	{
		$gateways['ezdefi'] = array(
			'admin_label' => __( 'EZDefi', 'edd-ezdefi' ),
			'checkout_label' => __( 'EZDefi', 'edd-ezdefi' )
		);

		return $gateways;
	}

	/** Listener callback */
	public function listen_for_action()
	{
		$base_url = '/edd-ezdefi/';
		$url = $_SERVER['REQUEST_URI'];

		if( strpos( $url, $base_url ) !== 0 ) {
			return '';
		}

		$url = substr( $url, strlen( $base_url ) );
		$url = parse_url( $url, PHP_URL_PATH );
		$pieces = explode( '/', $url );

		if( !$pieces['0'] ) {
			return '';
		}

		$action = $pieces['0'];

		if( $action === 'nextypay' ) {
			do_action( 'edd_ezdefi_nextypay' );
		}
	}

	/**
	 * Get EDD_Ezdefi instance
	 */
	public static function instance()
	{
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}