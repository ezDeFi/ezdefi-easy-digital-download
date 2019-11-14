<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezpay
{
	protected static $instance;

	/** EDD_Ezpay constructor */
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
	    define( 'EDD_EZPAY_FILE', __FILE__ );
	}

	/** Includes required files */
	public function includes()
	{
		require_once( $this->plugin_path() . '/includes/install.php' );
		require_once( $this->plugin_path() . '/includes/class-edd-ezpay-db.php' );
		require_once( $this->plugin_path() . '/includes/class-edd-ezpay-cron.php' );
		require_once( $this->plugin_path() . '/includes/class-edd-ezpay-api.php' );
		require_once( $this->plugin_path() . '/includes/class-edd-ezpay-ajax.php' );
		require_once( $this->plugin_path() . '/includes/class-edd-ezpay-frontend.php' );
		require_once( $this->plugin_path() . '/includes/class-edd-ezpay-payment.php' );
		require_once( $this->plugin_path() . '/includes/class-edd-ezpay-shortcode.php' );

		if( defined( 'DOING_CRON' ) || is_admin() ) {
			require_once( $this->plugin_path() . '/includes/admin/class-edd-ezpay-admin.php' );
		}
	}

	/** Get the plugin url */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', EDD_EZPAY_FILE ) );
	}

	/** Get the plugin path */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( EDD_EZPAY_FILE ) );
	}

	/** Register Ezpay gateway */
	public function register_gateway( $gateways )
	{
		$gateways['ezpay'] = array(
			'admin_label' => __( 'EZPay', 'edd-ezpay' ),
			'checkout_label' => __( 'EZPay', 'edd-ezpay' )
		);

		return $gateways;
	}

	/** Listener callback */
	public function listen_for_action()
	{
		$base_url = '/edd-ezpay/';
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
			do_action( 'edd_ezpay_nextypay' );
		}
	}

	/**
	 * Get EDD_Ezpay instance
	 */
	public static function instance()
	{
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}