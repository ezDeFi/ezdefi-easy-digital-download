<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class EDD_EZPay_Payment
{
    private $api;

    public function init()
    {
        $this->add_action();
        $this->load_dependencies();
    }

    private function load_dependencies()
    {
        $config = [];
        $config['url'] = rtrim( edd_get_option( 'ezpay_api_url' ), '/\\' );
        $config['key'] = edd_get_option( 'ezpay_api_key' );

        require_once EZPAY_PLUGIN_PATH . 'class-api.php';
        $this->api = new EDD_EZPay_Api($config);
    }

    public function add_action()
    {
        add_action( 'edd_gateway_ezpay', array( $this, 'process_payment' ) );
    }

    public function process_payment($purchase_data)
    {
        $payment_id = $this->create_payment($purchase_data);

        $ezpay_currency = [
            'id' => $purchase_data['post_data']['edd_ezpay_currency_id'],
            'symbol' => $purchase_data['post_data']['edd_ezpay_currency_symbol']
        ];

        $this->create_ezpay_payment($payment_id, $ezpay_currency);
    }

    private function create_payment($purchase_data)
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

        $payment = edd_insert_payment($payment_data);

        if(!$payment) {
            // return error
        }

        return $payment;
    }

    private function create_ezpay_payment($payment_id, $ezpay_currency)
    {
        global $wp;

        $payment = edd_get_payment($payment_id);

        $config = $this->find_currency_config($ezpay_currency['id']);

        if(!$config) {
            // return error
        }

        $data = [
            'uoid' => $payment_id,
            'to' => $config['wallet'],
            'value' => $payment->subtotal,
            'currency' => $payment->currency . '/' . $ezpay_currency['symbol'],
            'safedist' => $config['distance'],
            'ucid' => $payment->user_id,
            'duration' => $config['lifetime'],
//            'callback' => home_url( $wp->request ) . '/edd-ezpay-api/nextypay'
            'callback' => 'http://a4cc12ec.ngrok.io/edd-ezpay-api/nextypay'
        ];

        $ezpay_payment = $this->api->callApi('payment/create', 'post', $data);

        if(!$ezpay_payment) {
            //
        }

        $this->set_ezpay_session( 'edd_payment_id', $payment_id );
        $this->set_ezpay_session( 'edd_ezpay_payment', json_decode( $ezpay_payment, true ) );

        edd_send_back_to_checkout( '?payment-mode=ezpay' );
    }

    private function set_ezpay_session($key, $data)
    {
        EDD_EZPay()->edd_ezpay_session->set($key, $data );

        return;
    }

    private function find_currency_config($currency_id)
    {
        $config = edd_get_option( 'ezpay_currency' );

        foreach($config as $c) {
            if($c['id'] == $currency_id) {
                return $c;
            }
        }

        return false;
    }
}