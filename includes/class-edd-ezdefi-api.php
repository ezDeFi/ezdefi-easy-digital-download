<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezdefi_Api
{
	protected $api_url;

	protected $api_key;

	protected $db;

	public function __construct( $api_url = '', $api_key = '' ) {
		$this->api_url = $api_url;
		$this->api_key = $api_key;

		$this->db = new EDD_Ezdefi_Db();
	}

	public function set_api_url( $api_url )
	{
		$this->api_url = $api_url;
	}

	public function get_api_url()
	{
		if( empty( $this->api_url ) ) {
			$api_url = edd_get_option( 'ezdefi_api_url' );
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
			$api_key = edd_get_option( 'ezdefi_api_key' );
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

    public function create_ezdefi_payment( $edd_payment, $currency_data, $amountId = false )
    {
    	$value = $this->calculate_discounted_price( $edd_payment->subtotal, $currency_data['discount'] );

	    if( $amountId ) {
		    $value = $this->generate_amount_id(
			    $edd_payment->currency,
			    $currency_data['symbol'],
			    $value,
			    $currency_data
		    );
	    }

	    if( ! $value ) {
		    return new WP_Error( 'create_ezdefi_payment', 'Can not create payment.' );
	    }

	    $uoid = $this->generate_uoid( $edd_payment->ID, $amountId );

	    $data = [
		    'uoid' => $uoid,
		    'to' => ( isset( $currency_data['wallet'] ) ? $currency_data['wallet'] : '' ),
		    'value' => $value,
		    'safedist' => (isset($currency_data['distance'])) ? $currency_data['distance'] : '',
//            'ucid' => $edd_payment->user_id,
		    'ucid' => rand(1, 100),
		    'duration' => (isset($currency_data['lifetime'])) ? $currency_data['lifetime'] : '',
            'callback' => home_url() . '/?edd-ezdefi-callback'
	    ];

	    if( $amountId ) {
		    $data['amountId'] = true;
		    $data['currency'] = $currency_data['symbol'] . ':' . $currency_data['symbol'];
	    } else {
		    $data['currency'] = $edd_payment->currency . ':' . $currency_data['symbol'];
	    }

	    $response = $this->call( 'payment/create', 'post', $data );

	    return $response;
    }

    public function get_ezdefi_payment( $paymentid )
    {
	    $response = $this->call( 'payment/get', 'get', array(
	        'paymentid' => $paymentid
        ) );

	    return $response;
    }

    public function calculate_discounted_price( $price, $discount )
    {
	    if( intval( $discount ) > 0) {
		    return $price - ( $price * ( $discount / 100 ) );
	    }

	    return $price;
    }

	public function generate_amount_id( $fiat, $token, $value, $currency_data )
	{
		$rate = $this->get_token_exchange( $fiat, $token );

		if( ! $rate ) {
			return null;
		}

		$value = $value * $rate;

		$value = $this->db->generate_amount_id( $value, $currency_data );

		return $value;
	}

	public function get_token_exchange( $fiat, $token )
	{
		$response = $this->call( 'token/exchange/' . $fiat . ':' . $token, 'get' );

		if( is_wp_error( $response ) ) {
			return null;
		}

		$response = json_decode( $response['body'], true );

		if( $response['code'] < 0 ) {
			return null;
		}

		return $response['data'];
	}

	public function generate_uoid( $uoid, $amountId )
	{
		if( $amountId ) {
			return $uoid . '-1';
		}

		return $uoid = $uoid . '-0';
	}

    public function get_list_wallet()
    {
	    $response = $this->call( 'user/list_wallet', 'get', array() );

	    return $response;
    }

	public function check_api_key()
	{
		$response = $this->call( 'user/show', 'get' );

		return $response;
	}

	public function get_list_currency( $keyword = '' )
	{
		$response = $this->call( 'token/list', 'get', array(
			'keyword' => $keyword
		) );

		return $response;
	}

	public function get_token_exchanges( $value, $from, $to )
	{
		$url = "token/exchanges?amount=$value&from=$from&to=$to";

		$response = $this->call( $url, 'get' );

		if( is_wp_error( $response ) ) {
			return null;
		}

		$response = json_decode( $response['body'], true );

		if( $response['code'] < 0 ) {
			return null;
		}

		return $response['data'];
	}

	public function get_transaction( $id )
	{
		$response = $this->call( 'transaction/get', 'get', array(
			'id' => $id
		) );
		return $response;
	}
}