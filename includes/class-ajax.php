<?php

class EDD_EZPay_Ajax
{
    protected static $instance = null;

    /** Only one instance of EDD_EZPay_Ajax can be loaded */
    public static function instance()
    {
        if( is_null( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function init()
    {
        add_action( 'wp_ajax_edd_ezpay_check_wallet', array( $this, 'check_ezpay_wallet' ) );
        add_action( 'wp_ajax_nopriv_edd_ezpay_check_wallet', array( $this, 'check_ezpay_wallet' ) );

        add_action( 'wp_ajax_check_payment_status', array( $this, 'check_payment_status' ) );
        add_action( 'wp_ajax_nopriv_check_payment_status', array( $this, 'check_payment_status' ) );
    }

    /** Ajax check wallet handle */
    public function check_ezpay_wallet()
    {
        $address = $_POST['address'];

        $response = $this->get_api()->callApi('user/list_wallet', 'get');

        if( is_wp_error( $response ) ) {
        }

        $response = json_decode( $response['body'], true );

        $list_wallet = $response['data'];

        $key = array_search($address, array_column($list_wallet, 'address'));

        wp_die($list_wallet[$key]['status']);
    }

    /** AJAX callback to check edd payment status */
    public function check_payment_status()
    {
        $payment_id = $_POST['paymentId'];

        $payment_status = edd_get_payment_status( $payment_id, true );

        wp_die($payment_status);
    }

    /** Get API instance */
    protected function get_api()
    {
        return EDD_EZPay()->api;
    }
}