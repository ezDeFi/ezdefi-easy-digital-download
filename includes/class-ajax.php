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
        add_action( 'wp_ajax_edd_ezpay_get_currency', array( $this, 'get_ezpay_currency' ) );
        add_action( 'wp_ajax_nopriv_edd_ezpay_get_currency', array( $this, 'get_ezpay_currency' ) );

        add_action( 'wp_ajax_edd_ezpay_check_wallet', array( $this, 'check_ezpay_wallet' ) );
        add_action( 'wp_ajax_nopriv_edd_ezpay_check_wallet', array( $this, 'check_ezpay_wallet' ) );

        add_action( 'wp_ajax_check_payment_status', array( $this, 'check_payment_status' ) );
        add_action( 'wp_ajax_nopriv_check_payment_status', array( $this, 'check_payment_status' ) );

        add_action( 'wp_ajax_create_ezpay_payment', array( $this, 'create_ezpay_payment' ) );
        add_action( 'wp_ajax_nopriv_create_ezpay_payment', array( $this, 'create_ezpay_payment' ) );
    }

    /** Ajax get currency */
    public function get_ezpay_currency()
    {
        $url = rtrim( $_POST['api_url'], '/' ) . '/token/list';
        $keyword = $_POST['keyword'];

        $data = array(
            'keyword' => $keyword
        );

        $url = sprintf("%s?%s", $url, http_build_query( $data ) );

        $response = $this->call_api( $url );

        if( is_wp_error( $response ) ) {
            wp_send_json_success( __( 'Can not get currency', 'edd-ezpay' ) );
        }

        $ezpay_currency = json_decode( $response['body'], true );

        wp_send_json_success($ezpay_currency['data']);
    }

    /** Ajax check wallet handle */
    public function check_ezpay_wallet()
    {
        $apiUrl = $_POST['api_url'];
        $apiKey = $_POST['api_key'];
        $address = $_POST['address'];
        $url = rtrim( $apiUrl, '/' ) . '/user/list_wallet';

        $response = $this->call_api( $url, $apiKey );

        if( is_wp_error( $response ) ) {
            wp_die('false');
        }

        $response = json_decode( $response['body'], true );

        $list_wallet = $response['data'];

        $key = array_search($address, array_column($list_wallet, 'address'));

        $status = $list_wallet[$key]['status'];

        if($status === 'ACTIVE') {
            wp_die('true');
        }

        wp_die('false');
    }

    /** AJAX callback to check edd payment status */
    public function check_payment_status()
    {
        $payment_id = $_POST['paymentId'];

        $payment_status = edd_get_payment_status( $payment_id, true );

        wp_die($payment_status);
    }

    /** AJAX callback to create ezPay payment */
    public function create_ezpay_payment()
    {
        $edd_payment_id = $_POST['uoid'];
        $symbol = $_POST['symbol'];

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
            'callback' => 'http://877be640.ngrok.io/edd-ezpay/nextypay'
        ];

        $response = $this->get_api()->callApi('payment/create', 'post', $data);

        if( is_wp_error( $response ) ) {
            wp_send_json_success( __( 'Create Ezpay payment failed', 'edd-ezpay' ) );
        }

        $ezpay_payment = json_decode( $response['body'], true );

        EDD()->session->set( 'edd_ezpay_payment', $ezpay_payment );

        wp_send_json_success($ezpay_payment);
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

    /** Call to API */
    protected function call_api($url, $key = '')
    {
        $headers = array(
            'api-key' => $key,
            'accept' => 'application/xml',
        );

        return wp_remote_get(
            $url,
            array( 'headers' => $headers )
        );
    }

    /** Get API instance */
    protected function get_api()
    {
        return EDD_EZPay()->api;
    }
}