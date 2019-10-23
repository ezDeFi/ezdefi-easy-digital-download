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
        // If client not choose currency, set error then send back to checkout page
        if( !isset( $purchase_data['post_data']['edd_ezpay_currency_symbol'] ) ) {
            edd_set_error( 'missing_ezpay_currency', __( 'Missing Ezpay Currency', 'edd-ezpay' ) );
            return $this->redirect_back_to_checkout();
        }

        $ezpay_currency_symbol = $purchase_data['post_data']['edd_ezpay_currency_symbol'];

        $ezpay_currency_config = $this->get_ezpay_currency_config( $ezpay_currency_symbol );

        // If client choose not accepted currency, set error then send back to checkout page
        if( !$ezpay_currency_config ) {
            edd_set_error( 'ezpay_currency_not_accepted', __( 'This currency is not accepted', 'edd-ezpay' ) );
            return $this->redirect_back_to_checkout();
        }

        // Create EDD Payment
        $edd_payment_id = $this->create_edd_payment($purchase_data);

        // Create EZPay Payment
        $this->create_ezpay_payment($edd_payment_id, $ezpay_currency_config);
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

    /** Create EZPay Payment then redirect to QRCode Page */
    protected function create_ezpay_payment($edd_payment_id, $currency_data)
    {
        global $wp;

        $edd_payment = edd_get_payment($edd_payment_id);

        $data = [
            'uoid' => $edd_payment_id,
            'to' => ( isset( $currency_data['wallet'] ) ? $currency_data['wallet'] : '' ),
            'value' => $edd_payment->subtotal,
            'currency' => $edd_payment->currency . '/' . $currency_data['symbol'],
            'safedist' => (isset($currency_data['distance'])) ? $currency_data['distance'] : '',
            'ucid' => $edd_payment->user_id,
            'duration' => (isset($currency_data['lifetime'])) ? $currency_data['lifetime'] : '',
//            'callback' => home_url( $wp->request ) . '/edd-ezpay/nextypay'
            'callback' => 'http://bc18b943.ngrok.io/edd-ezpay/nextypay'
        ];

        $response = $this->get_api()->callApi('payment/create', 'post', $data);

        if( is_wp_error( $response ) ) {
            edd_set_error( 'ezpay_payment_fail', __( 'Create Ezpay payment failed', 'edd-ezpay' ) );
            return $this->redirect_back_to_checkout();
        }

        $ezpay_payment = json_decode( $response['body'], true );

        EDD()->session->set( 'edd_ezpay_payment', $ezpay_payment );

        return $this->send_to_qrcode_page();
    }

    /** Redirect client back to checkout page */
    public function redirect_back_to_checkout()
    {
        edd_send_back_to_checkout( '?payment-mode=ezpay' );
    }

    public function send_to_qrcode_page()
    {
        wp_redirect( edd_ezpay_get_qrcode_page_uri() );
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
        EDD()->session->set( 'edd_ezpay_payment', '' );
        edd_update_payment_status( $payment_id, 'publish' );
        edd_empty_cart();
    }

    /** Get config for ezpay currency */
    private function get_ezpay_currency_config($symbol)
    {
        $config = edd_get_option( 'ezpay_currency' );

        foreach($config as $c) {
            if($c['symbol'] == $symbol) {
                return $c;
            }
        }

        // return false if currency isn't accepted
        return false;
    }

    /** Get API instance */
    protected function get_api()
    {
        return EDD_EZPay()->api;
    }
}