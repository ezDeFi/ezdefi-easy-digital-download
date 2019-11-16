<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezpay_Payment
{
	protected $api;

	/** EDD_Ezpay_Payment constructor. */
    public function __construct()
    {
    	$this->api = new EDD_Ezpay_Api();

        add_action( 'edd_gateway_ezpay', array( $this, 'process_payment' ) );
        add_action( 'edd_ezpay_nextypay', array( $this, 'gateway_callback_handle' ) );
    }

    /** Process payment for EZPay Gateway */
    public function process_payment($purchase_data)
    {
	    $edd_payment_id = $this->create_edd_payment($purchase_data);

	    if( ! $edd_payment_id ) {
		    edd_send_back_to_checkout( '?payment-mode=ezpay' );
	    }

	    $edd_payment = edd_get_payment( $edd_payment_id );

	    $symbol = $purchase_data['post_data']['edd_ezpay_currency_symbol'];

	    $ezpay_payment = $this->create_ezpay_payment_loop( $edd_payment, $symbol );

	    $this->update_payment_meta_data( $edd_payment, array(
		    '_edd_ezpay_payment' => $ezpay_payment,
		    '_edd_ezpay_currency' => $symbol
	    ) );

        edd_send_to_success_page();
    }

	private function find_currency_data( $symbol ) {
		$currency = edd_get_option( 'ezpay_currency' );

		$index = array_search( $symbol, array_column( $currency, 'symbol' ) );

		if( $index === false ) {
			return false;
		}

		return $currency[$index];
	}

	private function update_payment_meta_data( $payment, $data )
	{
		foreach( $data as $key => $value ) {
			$payment->update_meta( $key, $value );
		}

		$payment->save();
	}

	private function create_ezpay_payment_loop( $edd_payment, $symbol )
	{
		$currency_data = $this->find_currency_data( $symbol );

		if( $currency_data === false ) {
			return $this->create_ezpay_payment_fail();
		}

		$data = array();

		$method = edd_get_option( 'ezpay_method' );

		foreach( $method as $key => $value ) {
			$amount_id = ( $key === 'amount_id' ) ? true : false;
			$payment = $this->create_ezpay_payment( $edd_payment, $currency_data, $amount_id );
			$data[$key] = $payment['_id'];
		}

		return $data;
	}

	private function create_ezpay_payment( $edd_payment, $currency_data, $amount_id )
	{
		$response = $this->api->create_ezpay_payment( $edd_payment, $currency_data, $amount_id );

		if( is_wp_error( $response ) ) {
			return $this->create_ezpay_payment_fail();
		}

		$response = json_decode( $response['body'], true );

		if( intval( $response['code'] ) < 0 && isset( $response['error'] ) ) {
			return $this->create_ezpay_payment_fail();
		}

		return $response['data'];
	}

	private function create_ezpay_payment_fail()
	{
		return edd_send_back_to_checkout( '?payment-mode=ezpay' );
	}

	private function set_amount_id_invalid( $amount_id, $currency )
	{
		$this->db->set_amount_id_invalid( $amount_id, $currency );
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
	        edd_send_back_to_checkout( '?payment-mode=ezpay' );
        }

        return $payment_id;
    }

    /** Gateway callback handle */
    public function gateway_callback_handle()
    {
	    if( ! isset( $_GET['uoid'] ) || ! isset( $_GET['paymentid'] ) ) {
		    wp_die();
	    }

        $edd_payment_id = $_GET['uoid'];

	    $edd_payment = edd_get_payment( $edd_payment_id );

	    if( ! $edd_payment ) {
	    	wp_die();
	    }

        $ezpay_payment_id = $_GET['paymentid'];

        $response = $this->api->get_ezpay_payment( $ezpay_payment_id );

        if( is_wp_error( $response ) ) {
            wp_die();
        }

        $ezpay_payment_data = json_decode( $response['body'], true );

        $status = $ezpay_payment_data['data']['payment']['status'];

        if( $status === 'DONE' ) {
	        edd_update_payment_status( $edd_payment_id, 'publish' );

	        $ezpay_amount_id = $edd_payment->get_meta( '_edd_ezpay_payment' );

	        if( $ezpay_amount_id && ! empty( $ezpay_amount_id ) ) {
		        $this->set_amount_id_invalid(
			        $ezpay_amount_id,
			        $edd_payment->get_meta( '_edd_ezpay_currency' )
		        );
	        }

	        edd_empty_cart();
        }

	    wp_die();
    }
}

new EDD_Ezpay_Payment();
