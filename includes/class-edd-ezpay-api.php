<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezpay_Api
{
	protected $api_url;

	protected $api_key;

	protected $db;

	public function __construct( $api_url = '', $api_key = '' ) {
		$this->api_url = $api_url;
		$this->api_key = $api_key;

		$this->db = new EDD_Ezpay_Db();
	}

	public function set_api_url( $api_url )
	{
		$this->api_url = $api_url;
	}

	public function get_api_url()
	{
		if( empty( $this->api_url ) ) {
			$api_url = edd_get_option( 'ezpay_api_url' );
			$this->set_api_url( $api_url );
		}

		return $this->api_url;
	}

	public function set_api_key( $api_key )
	{
		$this->api_key = $api_key;
	}

	public function get_api_key()
	{
		if( empty( $this->api_key ) ) {
			$api_key = edd_get_option( 'ezpay_api_key' );
			$this->set_api_key( $api_key );
		}

		return $this->api_key;
	}

	public function build_path($path)
	{
		return rtrim( $this->get_api_url(), '/' ) . '/' . $path;
	}

	public function get_headers()
	{
		$headers = array(
			'api-key' => $this->get_api_key(),
			'accept' => 'application/xml',
		);

		return $headers;
	}

	public function call($path, $method = 'GET', $data = [])
	{
		$url = $this->build_path( $path ) ;

		$method = strtolower( $method );

		$headers = $this->get_headers();

		if($method === 'post') {
			return wp_remote_post( $url, array(
				'headers' => $headers,
				'body' => $data
			) );
		}

		if( ! empty( $data ) ) {
			$url = sprintf("%s?%s", $url, http_build_query( $data ) );
		}

		return wp_remote_get( $url, array( 'headers' => $headers ) );
	}

    public function create_ezpay_payment( $edd_payment, $currency_data, $amountId = false )
    {
	    $subtotal = intval($edd_payment->subtotal);
	    $discount = intval($currency_data['discount']);
	    $value = $subtotal - ($subtotal * ($discount / 100));

	    if( $amountId ) {
		    $value = $this->db->generate_amount_id( $value, $currency_data['symbol'] );
	    }

	    if( ! $value ) {
		    return new WP_Error( 'create_ezpay_payment', 'Can not generate amountID.' );
	    }

	    $uoid = $edd_payment->ID;

	    if( $amountId ) {
		    $uoid = $uoid . '-1';
	    } else {
		    $uoid = $uoid . '-0';
	    }

	    $data = [
		    'uoid' => $uoid,
		    'to' => ( isset( $currency_data['wallet'] ) ? $currency_data['wallet'] : '' ),
		    'value' => $value,
		    'currency' => $edd_payment->currency . ':' . $currency_data['symbol'],
		    'safedist' => (isset($currency_data['distance'])) ? $currency_data['distance'] : '',
//            'ucid' => $edd_payment->user_id,
		    'ucid' => rand(1, 100),
		    'duration' => (isset($currency_data['lifetime'])) ? $currency_data['lifetime'] : '',
//            'callback' => home_url() . '/edd-ezpay/nextypay'
	        'callback' => 'http://eae24f85.ngrok.io/edd-ezpay/nextypay',
		    'amountId' => $amountId
	    ];

	    $response = $this->call( 'payment/create', 'post', $data );

	    return $response;
    }

    public function get_ezpay_payment( $paymentid )
    {
	    $response = $this->call( 'payment/get', 'get', array(
	        'paymentid' => $paymentid
        ) );

	    return $response;
    }

    public function get_list_wallet()
    {
	    $response = $this->call( 'user/list_wallet', 'get', array() );

	    return $response;
    }

	public function get_list_currency( $keyword = '' )
	{
		$response = $this->call( 'token/list', 'get', array(
			'keyword' => $keyword
		) );

		return $response;
	}
}