<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class EDD_EZPay_Class
{
    public $api;

    public $redirect;

    protected static $instance = null;

    /** Only one instance of EDD_EZPay_Class can be loaded */
    public static function instance()
    {
        if( is_null( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /** Constructor */
    public function init()
    {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    /** Define EDD_EZPay Constants */
    public function define_constants()
    {
        if( !defined( 'EDD_EZPAY_API_PATH' ) ) {
            define('EDD_EZPAY_API_PATH', '/edd-ezpay/');
        }
    }

    /** Includes required file */
    public function includes()
    {
        include_once $this->plugin_path() . '/includes/functions.php';

        include_once $this->plugin_path() . '/includes/class-ajax.php';
        EDD_EZPay_Ajax::instance()->init();

        include_once $this->plugin_path() . '/includes/class-install.php';
        EDD_EZPay_Install::instance()->init();

        include_once $this->plugin_path() . '/includes/class-shortcode.php';
        EDD_EZPay_Shortcode::instance()->init();

        include_once $this->plugin_path() . '/includes/class-listener.php';

        include_once $this->plugin_path() . '/includes/class-front-end.php';
        EDD_EZPay_Front_End::instance()->init();

        include_once $this->plugin_path() . '/includes/class-api.php';
        $this->api = EDD_EZPay_Api::instance();

        include_once $this->plugin_path() . '/includes/class-redirect.php';
        $this->redirect = EDD_EZPay_Redirect::instance();

        include_once $this->plugin_path() . '/includes/class-payment.php';
        EDD_EZPay_Payment::instance()->init();

        if( is_admin() ) {
            include_once $this->plugin_path() . '/includes/class-admin.php';
            (new EDD_EZPay_Admin())->init();
        }
    }

    /** Hook into action and filter */
    public function init_hooks()
    {
        // Register new edd gateway
        add_filter( 'edd_payment_gateways', array( $this, 'register_gateway' ), 1, 1 );

        // Init listener for EZPay Payment
        add_action( 'init', array( $this, 'listen_for_action' ), 1, 1 );
    }

    /** Register gateway callback */
    public function register_gateway($gateways)
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
        $listener = new EDD_EZPay_Listener( EDD_EZPAY_API_PATH );
        $action = $listener->get_action( $_SERVER['REQUEST_URI'] );

        if( !empty( $action ) ) {
            do_action( 'edd_ezpay_' . $action );
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
}