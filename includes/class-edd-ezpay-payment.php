<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezpay_Payment
{
	protected $api;

	/** EDD_Ezpay_Payment constructor. */
    public function __construct()
    {
    	$api_url = edd_get_option( 'ezpay_api_url' );
    	$api_key = edd_get_option( 'ezpay_api_key' );

    	$this->api = new EDD_Ezpay_Api( $api_url, $api_key );

        add_action( 'edd_gateway_ezpay', array( $this, 'process_payment' ) );
        add_action( 'edd_ezpay_nextypay', array( $this, 'gateway_callback_handle' ) );
    }

    /** Process payment for EZPay Gateway */
    public function process_payment($purchase_data)
    {
        $edd_payment_id = $this->create_edd_payment($purchase_data);
	    $edd_payment = edd_get_payment( $edd_payment_id );

	    $symbol = $purchase_data['post_data']['edd_ezpay_currency_symbol'];

	    $currency = edd_get_option( 'ezpay_currency' );
	    $index = array_search( $symbol, array_column( $currency, 'symbol' ) );
	    $currency_data = $currency[$index];

	    $response = $this->api->create_ezpay_payment( $edd_payment, $currency_data, true );

	    if( is_wp_error( $response ) ) {
		    edd_send_back_to_checkout( '?payment-mode=ezpay' );
	    }

	    $response = json_decode( $response['body'], true );

	    $ezpay_payment = $response['data'];

	    $edd_payment->update_meta( '_edd_ezpay_payment', $ezpay_payment['_id'] );
	    $edd_payment->update_meta( '_edd_ezpay_currency', $symbol );
	    $edd_payment->save();

        edd_send_to_success_page();
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
        $edd_payment_id = $_GET['uoid'];
        $ezpay_payment_id = $_GET['paymentid'];

        $response = $this->api->get_ezpay_payment( $ezpay_payment_id );

        if( is_wp_error( $response ) ) {
            wp_die();
        }

        $ezpay_payment_data = json_decode( $response['body'], true );

        $status = $ezpay_payment_data['data']['payment']['status'];

        if( $status === 'DONE' ) {
	        edd_update_payment_status( $edd_payment_id, 'publish' );
	        edd_empty_cart();
        }

	    wp_die();
    }
}

new EDD_Ezpay_Payment();
