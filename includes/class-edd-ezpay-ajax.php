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

	    add_action( 'wp_ajax_edd_ezpay_get_payment', array( $this, 'edd_ezpay_get_payment_ajax_callback' ) );
	    add_action( 'wp_ajax_nopriv_edd_ezpay_get_payment', array( $this, 'edd_ezpay_get_payment_ajax_callback' ) );

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

	public function edd_ezpay_get_payment_ajax_callback()
	{
		$data = $this->validate_post_data( $_POST, __( 'Can not get payment', 'edd-ezpay' ) );

		$order = $data['order'];

		$ezpay_payment = ( $order->get_meta( '_edd_ezpay_payment' ) ) ? $order->get_meta( '_edd_ezpay_payment' ) : array();

		$method = $data['method'];

		if( array_key_exists( $method, $ezpay_payment ) && $ezpay_payment[$method] !== '' ) {
			$paymentid = $ezpay_payment[$method];
			return $this->get_ezpay_payment( $paymentid );
		}

		$symbol = $_POST['symbol'];

		return $this->create_ezpay_payment( $order, $symbol, $method );
	}

    /** AJAX callback to create ezPay payment */
    public function edd_ezpay_create_payment_ajax_callback()
    {
	    $data = $this->validate_post_data( $_POST, __( 'Can not create payment', 'edd-ezpay' ) );

	    $symbol = $_POST['symbol'];

	    return $this->create_ezpay_payment( $data['order'], $symbol, $data['method'], true );
    }

	private function validate_post_data( $data, $message = '' )
	{
		if( ! isset( $data['uoid'] ) || ! isset( $data['symbol'] ) || ! isset( $data['method'] ) ) {
			wp_send_json_error( $message );
		}

		$uoid = $_POST['uoid'];

		$data = array();

		$data['order'] = $this->get_order( $uoid, $message );

		$data['method'] = $this->validate_payment_method( $_POST['method'], $message );

		return $data;
	}

	private function validate_payment_method( $method, $message )
	{
		$accepted_method = edd_get_option( 'ezpay_method' );

		if( ! array_key_exists( $method, $accepted_method ) ){
			wp_send_json_error( $message );
		}

		return $method;
	}

	private function get_order( $uoid, $message )
	{
		$order = edd_get_payment( $uoid );

		if( ! $order ) {
			wp_send_json_error( $message );
		}

		return $order;
	}

	private function get_currency_data( $symbol, $message )
	{
		$currency = edd_get_option( 'ezpay_currency' );

		$index = array_search( $symbol, array_column( $currency, 'symbol' ) );

		if( $index === false ) {
			wp_send_json_error( $message );
		}

		return $currency[$index];
	}

	private function get_ezpay_payment( $paymentid )
	{
		$response = $this->api->get_ezpay_payment( $paymentid );

		if( is_wp_error( $response ) ) {
			wp_send_json_error( __( 'Can not get payment', 'edd-ezpay' ) );
		}

		$response = json_decode( $response['body'], true );

		$ezpay_payment = $response['data'];

		$html = edd_ezpay_generate_payment_html( $ezpay_payment );

		wp_send_json_success( $html );
	}

	private function create_ezpay_payment( $order, $symbol, $method, $clear_meta_data = false )
	{
		$currency_data = $this->get_currency_data( $symbol, __( 'Can not create payment', 'edd-ezpay' ) );

		$amount_id = ( $method === 'amount_id' ) ? true : false;

		$response = $this->api->create_ezpay_payment( $order, $currency_data, $amount_id );

		if( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message( 'create_ezpay_payment' );
			wp_send_json_error( $error_message );
		}

		$response = json_decode( $response['body'], true );

		$payment = $response['data'];

		$html = edd_ezpay_generate_payment_html( $payment );

		if( $clear_meta_data ) {
			$ezpay_payment = array();
		} else {
			$ezpay_payment = ( $order->get_meta( '_edd_ezpay_payment' ) ) ? $order->get_meta( '_edd_ezpay_payment' ) : array();
		}

		$ezpay_payment[$method] = $payment['_id'];

		$order->update_meta( '_edd_ezpay_payment', $ezpay_payment );
		$order->update_meta( '_edd_ezpay_currency', $symbol );
		$order->save();

		wp_send_json_success( $html );
	}
}

new EDD_EZPay_Ajax();