<?php

defined( 'ABSPATH' ) or exit;

class EDD_EZPay_Ajax
{
	protected $api;

    public function __construct()
    {
	    $this->api = new EDD_Ezpay_Api();

        add_action( 'wp_ajax_edd_ezpay_get_currency', array( $this, 'edd_ezpay_get_currency_ajax_callback' ) );
        add_action( 'wp_ajax_nopriv_edd_ezpay_get_currency', array( $this, 'edd_ezpay_get_currency_ajax_callback' ) );

        add_action( 'wp_ajax_edd_ezpay_check_wallet', array( $this, 'edd_ezpay_check_wallet_ajax_callback' ) );
        add_action( 'wp_ajax_nopriv_edd_ezpay_check_wallet', array( $this, 'edd_ezpay_check_wallet_ajax_callback' ) );

        add_action( 'wp_ajax_edd_ezpay_check_payment_status', array( $this, 'edd_ezpay_check_payment_status_ajax_callback' ) );
        add_action( 'wp_ajax_nopriv_edd_ezpay_check_payment_status', array( $this, 'edd_ezpay_check_payment_status_ajax_callback' ) );

        add_action( 'wp_ajax_edd_ezpay_create_payment', array( $this, 'edd_ezpay_create_payment_ajax_callback' ) );
        add_action( 'wp_ajax_nopriv_edd_ezpay_create_payment', array( $this, 'edd_ezpay_create_payment_ajax_callback' ) );
    }

	/** Get currency ajax callback */
    public function edd_ezpay_get_currency_ajax_callback()
    {
	    $keyword = $_POST['keyword'];
	    $api_url = $_POST['api_url'];

	    $api = new EDD_EZPay_Api( $api_url );

	    $response = $api->get_list_currency( $keyword );

	    if( is_wp_error( $response ) ) {
		    wp_send_json_error( __( 'Can not get currency', 'edd-ezpay' ) );
	    }

	    $response = json_decode( $response['body'], true );

	    $currency = $response['data'];

	    wp_send_json_success( $currency );
    }

	/** Check wallet address ajax callback */
	public function edd_ezpay_check_wallet_ajax_callback()
	{
		$address = $_POST['address'];
		$api_url = $_POST['api_url'];
		$api_key = $_POST['api_key'];

		$api = new EDD_EZPay_Api( $api_url, $api_key );

		$response = $api->get_list_wallet();

		if( is_wp_error( $response ) ) {
			wp_die( 'false' );
		}

		$response = json_decode( $response['body'], true );

		$list_wallet = $response['data'];

		$key = array_search( $address, array_column( $list_wallet, 'address' ) );

		$status = $list_wallet[$key]['status'];

		if($status === 'ACTIVE') {
			wp_die('true');
		}

		wp_die('false');
	}

    /** AJAX callback to check edd payment status */
    public function edd_ezpay_check_payment_status_ajax_callback()
    {
        $payment_id = $_POST['paymentId'];

        $payment_status = edd_get_payment_status( $payment_id, true );

        wp_die($payment_status);
    }

    /** AJAX callback to create ezPay payment */
    public function edd_ezpay_create_payment_ajax_callback()
    {
	    if( ! isset( $_POST['uoid'] ) || ! isset( $_POST['symbol'] ) ) {
		    wp_send_json_error( __( 'Can not create payment', 'edd-ezpay' ) );
	    }

        $edd_payment_id = $_POST['uoid'];

	    $edd_payment = edd_get_payment( $edd_payment_id );

	    if( ! $edd_payment ) {
		    wp_send_json_error( __( 'Can not create payment', 'edd-ezpay' ) );
	    }

        $symbol = $_POST['symbol'];

	    $currency = edd_get_option( 'ezpay_currency' );

	    $index = array_search( $symbol, array_column( $currency, 'symbol' ) );

	    if( $index === false ) {
		    wp_send_json_error( __( 'Can not create payment', 'edd-ezpay' ) );
	    }

	    $currency_data = $currency[$index];

	    $method = edd_get_option( 'ezpay_method' );

	    $ezpay_payment_data = array();

	    $html = '';

	    foreach( $method as $key => $value ) {
		    $amount_id = ( $key === 'amount_id' ) ? true : false;
		    $payment = $this->create_ezpay_payment( $edd_payment, $currency_data, $amount_id );
		    $ezpay_payment_data[$key] = $payment['_id'];
		    $html .= wc_ezpay_generate_payment_html( $payment );
	    }

	    $edd_payment->update_meta( '_edd_ezpay_payment', $ezpay_payment_data );
	    $edd_payment->update_meta( '_edd_ezpay_currency', $symbol );
	    $edd_payment->save();

	    wp_send_json_success( $html );
    }

	private function create_ezpay_payment( $edd_payment, $currency_data, $amount_id )
	{
		$response = $this->api->create_ezpay_payment( $edd_payment, $currency_data, $amount_id );

		if( is_wp_error( $response ) ) {
			wp_send_json_error( __( 'Can not create payment', 'edd-ezpay' ) );
		}

		$response = json_decode( $response['body'], true );

		return $response['data'];
	}
}

new EDD_EZPay_Ajax();