<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EDD_EZPay_Payment
{
    protected static $instance = null;

    /** Only one instance of EDD_EZPay_Class can be loaded */
    public static function instance()
    {
        if( is_null( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function init()
    {
        $this->init_hooks();
    }

    /** Hook into action and filter */
    private function init_hooks()
    {
        add_action( 'edd_gateway_ezpay', array( $this, 'process_payment' ) );
        add_action( 'edd_ezpay_nextypay', array( $this, 'nextypay_action_handle' ) );
    }

    /** Process payment for EZPay Gateway */
    public function process_payment($purchase_data)
    {
        $edd_payment_id = $this->create_edd_payment($purchase_data);

        $ezpay_payment = $this->create_ezpay_payment( $edd_payment_id, $purchase_data['post_data']['edd_ezpay_currency_symbol'] );

        if(!$ezpay_payment) {
            return $this->redirect_back_to_checkout();
        }

        EDD()->session->set( 'edd_ezpay_payment', $ezpay_payment );

        return $this->redirect_to_success_page();
    }

    /** Create pending EDD Payment */
    protected function create_edd_payment($purchase_data)
    {
        $payment_data = [
            'price'        => $purchase_data['price'],
            'date'         => $purchase_data['date'],
            'user_email'   => $purchase_data['user_email'],
            'purchase_key' => $purchase_data['purchase_key'],
            'currency'     => edd_get_currency(),
            'downloads'    => $purchase_data['downloads'],
            'user_info'    => $purchase_data['user_info'],
            'cart_details' => $purchase_data['cart_details'],
            'status'       => 'pending',
        ];

        $payment_id = edd_insert_payment($payment_data);

        if(!$payment_id) {
            return $this->redirect_back_to_checkout();
        }

        return $payment_id;
    }

    /** Create EZPay Payment */
    protected function create_ezpay_payment($edd_payment_id, $symbol)
    {
        $currency_data = $this->get_ezpay_currency_config( $symbol );
        $edd_payment = edd_get_payment( $edd_payment_id );

        $subtotal = intval($edd_payment->subtotal);
        $discount = intval($currency_data['discount']);
        $value = $subtotal - ($subtotal * ($discount / 100));

        $data = [
            'uoid' => $edd_payment_id,
            'to' => ( isset( $currency_data['wallet'] ) ? $currency_data['wallet'] : '' ),
            'value' => $value,
            'currency' => $edd_payment->currency . '/' . $currency_data['symbol'],
            'safedist' => (isset($currency_data['distance'])) ? $currency_data['distance'] : '',
//            'ucid' => $edd_payment->user_id,
            'ucid' => rand(1, 100),
            'duration' => (isset($currency_data['lifetime'])) ? $currency_data['lifetime'] : '',
//            'callback' => home_url() . '/edd-ezpay/nextypay'
            'callback' => 'http://1f60309d.ngrok.io/edd-ezpay/nextypay'
        ];

        $response = $this->get_api()->callApi('payment/create', 'post', $data);

        if( is_wp_error( $response ) ) {
            return false;
        }

        $ezpay_payment = json_decode( $response['body'], true );

        return $ezpay_payment;
    }

    /** Redirect client back to checkout page */
    public function redirect_back_to_checkout()
    {
        EDD()->session->set( 'edd_ezpay_payment', '' );

        edd_send_back_to_checkout( '?payment-mode=ezpay' );
    }

    /** Redirect client to ezpay checkout page */
    public function redirect_to_success_page()
    {
        edd_send_to_success_page();
    }

    /** Nexty payment callback handle */
    public function nextypay_action_handle()
    {
        $edd_payment_id = $_GET['uoid'];
        $ezpay_payment_id = $_GET['paymentid'];

        $data = array(
            'paymentid' => $ezpay_payment_id,
        );

        $response = $this->get_api()->callApi( 'payment/get', 'get', $data );

        if( is_wp_error( $response ) ) {
            wp_die();
        }

        $ezpay_payment_data = json_decode( $response['body'], true );

        $status = $ezpay_payment_data['data']['payment']['status'];

        if( $status === 'DONE' ) {
            $this->nextypay_success_handle($edd_payment_id);
        }
    }

    /** Nexty payment success handle */
    private function nextypay_success_handle($payment_id)
    {
        EDD()->session->set( 'edd_ezpay_uoid', '' );
        edd_update_payment_status( $payment_id, 'publish' );
        edd_empty_cart();
    }

    /** Get API instance */
    protected function get_api()
    {
        return EDD_EZPay()->api;
    }
    /** Get config for ezpay currency */
    protected function get_ezpay_currency_config($symbol)
    {
        $config = edd_get_option( 'ezpay_currency' );

        foreach($config as $c) {
            if($c['symbol'] == $symbol) {
                return $c;
            }
        }

        return false;
    }

}