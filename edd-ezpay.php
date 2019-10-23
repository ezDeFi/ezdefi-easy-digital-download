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

defined( 'ABSPATH' ) || exit;

//class EDD_EZPay_Class
//{
//    private static $instance;
//
//    public $edd_ezpay_init;
//    public $edd_ezpay_front_end;
//    public $edd_ezpay_admin;
//    public $edd_ezpay_payment;
//    public $edd_ezpay_session;
//
//    public static function instance()
//    {
//        if(!isset(self::$instance) && !(self::$instance instanceof EDD_EZPay)) {
//            self::$instance = new EDD_EZPay_Class();
//            self::$instance->setup_constants();
//            self::$instance->includes();
//
//                self::$instance->edd_ezpay_admin = new EDD_EZPay_Admin();
//                self::$instance->edd_ezpay_admin->init();
//
//
//            self::$instance->edd_ezpay_init = new EDD_EZPay_Init();
//            self::$instance->edd_ezpay_init->init();
//
//            self::$instance->edd_ezpay_front_end = new EDD_EZPay_Front_End();
//            self::$instance->edd_ezpay_front_end->init();
//
//            self::$instance->edd_ezpay_payment = new EDD_EZPay_Payment();
//            self::$instance->edd_ezpay_payment->init();
//
//            self::$instance->edd_ezpay_session = new EDD_EZPay_Session();
//
//            self::$instance->edd_ezpay_shortcode = new EDD_EZPay_Shortcode();
//            self::$instance->edd_ezpay_shortcode->init();
//        }
//
//        return self::$instance;
//    }
//
//    private function setup_constants()
//    {
//        define( 'EZPAY_PLUGIN_FILE', __FILE__ );
//        define( 'EZPAY_PLUGIN_URL', plugin_dir_url(__FILE__ ) );
//        define( 'EZPAY_PLUGIN_PATH', trailingslashit(plugin_dir_path(__FILE__) ) );
//        define( 'EZPAY_PLUGIN_API_PATH', '/edd-ezpay-api/' );
//    }
//
//    private function includes()
//    {
//        require_once EZPAY_PLUGIN_PATH . 'class-init.php';
//        require_once EZPAY_PLUGIN_PATH . 'class-front-end.php';
//        require_once EZPAY_PLUGIN_PATH . 'class-payment.php';
//        require_once EZPAY_PLUGIN_PATH . 'class-session.php';
//        require_once EZPAY_PLUGIN_PATH . 'class-shortcode.php';
//
//            require_once EZPAY_PLUGIN_PATH . 'class-admin.php';
//
//    }
//}

if( ! defined( 'EDD_EZPAY_FILE' ) ) {
    define( 'EDD_EZPAY_FILE', __FILE__ );
}

if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
    return;
}

if( ! class_exists( 'EDD_EZPay' ) ) {
    include_once dirname( __FILE__ ) . '/includes/class-edd-ezpay.php';
}

function EDD_EZPay() {
    $instance = EDD_EZPay_Class::instance();
    $instance->init();

    return $instance;
}

/** Load plugin after Easy Digital Downloads */
add_action( 'plugin_loaded', 'EDD_EZPay' );