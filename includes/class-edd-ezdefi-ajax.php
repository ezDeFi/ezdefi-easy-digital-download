<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezdefi_Ajax
{
	protected $api;

    public function __construct()
    {
	    $this->api = new EDD_Ezdefi_Api();

        add_action( 'wp_ajax_edd_ezdefi_get_currency', array( $this, 'edd_ezdefi_get_currency_ajax_callback' ) );
        add_action( 'wp_ajax_nopriv_edd_ezdefi_get_currency', array( $this, 'edd_ezdefi_get_currency_ajax_callback' ) );

        add_action( 'wp_ajax_edd_ezdefi_check_wallet', array( $this, 'edd_ezdefi_check_wallet_ajax_callback' ) );
        add_action( 'wp_ajax_nopriv_edd_ezdefi_check_wallet', array( $this, 'edd_ezdefi_check_wallet_ajax_callback' ) );

	    add_action( 'wp_ajax_edd_ezdefi_get_payment', array( $this, 'edd_ezdefi_get_payment_ajax_callback' ) );
	    add_action( 'wp_ajax_nopriv_edd_ezdefi_get_payment', array( $this, 'edd_ezdefi_get_payment_ajax_callback' ) );

        add_action( 'wp_ajax_edd_ezdefi_check_payment_status', array( $this, 'edd_ezdefi_check_payment_status_ajax_callback' ) );
        add_action( 'wp_ajax_nopriv_edd_ezdefi_check_payment_status', array( $this, 'edd_ezdefi_check_payment_status_ajax_callback' ) );

        add_action( 'wp_ajax_edd_ezdefi_create_payment', array( $this, 'edd_ezdefi_create_payment_ajax_callback' ) );
        add_action( 'wp_ajax_nopriv_edd_ezdefi_create_payment', array( $this, 'edd_ezdefi_create_payment_ajax_callback' ) );
    }

	/** Get currency ajax callback */
    public function edd_ezdefi_get_currency_ajax_callback()
    {
	    $keyword = $_POST['keyword'];
	    $api_url = $_POST['api_url'];

	    $api = new EDD_Ezdefi_Api( $api_url );

	    $response = $api->get_list_currency( $keyword );

	    if( is_wp_error( $response ) ) {
		    wp_send_json_error( __( 'Can not get currency', 'edd-ezdefi' ) );
	    }

	    $response = json_decode( $response['body'], true );

	    $currency = $response['data'];

	    wp_send_json_success( $currency );
    }

	/** Check wallet address ajax callback */
	public function edd_ezdefi_check_wallet_ajax_callback()
	{
		if( ! isset( $_POST['address'] ) || ! isset( $_POST['api_url'] ) || ! isset( $_POST['api_key'] ) ) {
			wp_die( 'false' );
		}

		$address = $_POST['address'];
		$api_url = $_POST['api_url'];
		$api_key = $_POST['api_key'];
		$currency_chain = strtolower( $_POST['currency_chain'] );

		$api = new EDD_Ezdefi_Api( $api_url, $api_key );

		$response = $api->get_list_wallet();

		if( is_wp_error( $response ) ) {
			wp_die( 'false' );
		}

		$response = json_decode( $response['body'], true );

		$list_wallet = $response['data'];

		$key = array_search( $address, array_column( $list_wallet, 'address' ) );

		if( $key === false ) {
			wp_die( 'false' );
		}

		$wallet = $list_wallet[$key];

		$status = strtolower( $wallet['status'] );

		$wallet_type = strtolower( $wallet['walletType'] );

		if( $status === 'active' && $wallet_type === $currency_chain ) {
			wp_die( 'true' );
		} else {
			wp_die( 'false' );
		}
	}

    /** AJAX callback to check edd payment status */
    public function edd_ezdefi_check_payment_status_ajax_callback()
    {
        $payment_id = $_POST['paymentId'];

        $payment_status = edd_get_payment_status( $payment_id, true );

        wp_die($payment_status);
    }

	public function edd_ezdefi_get_payment_ajax_callback()
	{
		$data = $this->validate_post_data( $_POST, __( 'Can not get payment', 'edd-ezdefi' ) );

		$order = $data['order'];

		$ezdefi_payment = ( $order->get_meta( '_edd_ezdefi_payment' ) ) ? $order->get_meta( '_edd_ezdefi_payment' ) : array();

		$method = $data['method'];

		if( array_key_exists( $method, $ezdefi_payment ) && $ezdefi_payment[$method] !== '' ) {
			$paymentid = $ezdefi_payment[$method];
			return $this->get_ezdefi_payment( $paymentid );
		}

		$symbol = $_POST['symbol'];

		return $this->create_ezdefi_payment( $order, $symbol, $method );
	}

    public function edd_ezdefi_create_payment_ajax_callback()
    {
	    $data = $this->validate_post_data( $_POST, __( 'Can not create payment', 'edd-ezdefi' ) );

	    $symbol = $_POST['symbol'];

	    return $this->create_ezdefi_payment( $data['order'], $symbol, $data['method'], true );
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
		$accepted_method = edd_get_option( 'ezdefi_method' );

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
		$currency = edd_get_option( 'ezdefi_currency' );

		$index = array_search( $symbol, array_column( $currency, 'symbol' ) );

		if( $index === false ) {
			wp_send_json_error( $message );
		}

		return $currency[$index];
	}

	private function get_ezdefi_payment( $paymentid )
	{
		$response = $this->api->get_ezdefi_payment( $paymentid );

		if( is_wp_error( $response ) ) {
			wp_send_json_error( __( 'Can not get payment', 'edd-ezdefi' ) );
		}

		$response = json_decode( $response['body'], true );

		$ezdefi_payment = $response['data'];

		$html = edd_ezdefi_generate_payment_html( $ezdefi_payment );

		wp_send_json_success( $html );
	}

	private function create_ezdefi_payment( $order, $symbol, $method, $clear_meta_data = false )
	{
		$currency_data = $this->get_currency_data( $symbol, __( 'Can not create payment', 'edd-ezdefi' ) );

		$amount_id = ( $method === 'amount_id' ) ? true : false;

		$response = $this->api->create_ezdefi_payment( $order, $currency_data, $amount_id );

		if( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message( 'create_ezdefi_payment' );
			wp_send_json_error( $error_message );
		}

		$response = json_decode( $response['body'], true );

		$payment = $response['data'];

		$html = edd_ezdefi_generate_payment_html( $payment );

		if( $clear_meta_data ) {
			$ezdefi_payment = array();
		} else {
			$ezdefi_payment = ( $order->get_meta( '_edd_ezdefi_payment' ) ) ? $order->get_meta( '_edd_ezdefi_payment' ) : array();
		}

		$ezdefi_payment[$method] = $payment['_id'];

		$order->update_meta( '_edd_ezdefi_payment', $ezdefi_payment );
		$order->update_meta( '_edd_ezdefi_currency', $symbol );
		$order->update_meta( '_edd_ezdefi_amount_id', $$payment['originValue'] );
		$order->save();

		wp_send_json_success( $html );
	}
}

new EDD_Ezdefi_Ajax();