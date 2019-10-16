<?php
/**
 * Plugin name: Nexty EDD Payment Gateway
 * Text Domain: edd-ezpay
 */

class EDD_EZPay_Class
{
    private static $instance;

    public $edd_ezpay_init;
    public $edd_ezpay_front_end;
    public $edd_ezpay_admin;
    public $edd_ezpay_payment;
    public $edd_ezpay_session;

    public static function instance()
    {
        if(!isset(self::$instance) && !(self::$instance instanceof EDD_EZPay)) {
            self::$instance = new EDD_EZPay_Class();
            self::$instance->setup_constants();
            self::$instance->includes();

                self::$instance->edd_ezpay_admin = new EDD_EZPay_Admin();
                self::$instance->edd_ezpay_admin->init();


            self::$instance->edd_ezpay_init = new EDD_EZPay_Init();
            self::$instance->edd_ezpay_init->init();

            self::$instance->edd_ezpay_front_end = new EDD_EZPay_Front_End();
            self::$instance->edd_ezpay_front_end->init();

            self::$instance->edd_ezpay_payment = new EDD_EZPay_Payment();
            self::$instance->edd_ezpay_payment->init();

            self::$instance->edd_ezpay_session = new EDD_EZPay_Session();

            self::$instance->edd_ezpay_shortcode = new EDD_EZPay_Shortcode();
            self::$instance->edd_ezpay_shortcode->init();
        }

        return self::$instance;
    }

    private function setup_constants()
    {
        define( 'EZPAY_PLUGIN_FILE', __FILE__ );
        define( 'EZPAY_PLUGIN_URL', plugin_dir_url(__FILE__ ) );
        define( 'EZPAY_PLUGIN_PATH', trailingslashit(plugin_dir_path(__FILE__) ) );
        define( 'EZPAY_PLUGIN_API_PATH', '/edd-ezpay-api/' );
    }

    private function includes()
    {
        require_once EZPAY_PLUGIN_PATH . 'class-init.php';
        require_once EZPAY_PLUGIN_PATH . 'class-front-end.php';
        require_once EZPAY_PLUGIN_PATH . 'class-payment.php';
        require_once EZPAY_PLUGIN_PATH . 'class-session.php';
        require_once EZPAY_PLUGIN_PATH . 'class-shortcode.php';

            require_once EZPAY_PLUGIN_PATH . 'class-admin.php';

    }
}

function EDD_EZPay() {
    return EDD_EZPay_Class::instance();
}

EDD_EZPay();