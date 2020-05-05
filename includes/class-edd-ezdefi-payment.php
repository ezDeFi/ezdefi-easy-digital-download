<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezdefi_Payment
{
	const EXPLORER_URL = 'https://explorer.nexty.io/tx/';

	protected $api;

	protected $db;

	/** EDD_Ezdefi_Payment constructor. */
    public function __construct()
    {
    	$this->api = new EDD_Ezdefi_Api();

    	$this->db = new EDD_Ezdefi_Db();

        add_action( 'edd_gateway_ezdefi', array( $this, 'process_payment' ) );
        add_action( 'edd_ezdefi_nextypay', array( $this, 'gateway_callback_handle' ) );
    }

    public function process_payment($purchase_data)
    {
        $coin_id = sanitize_text_field( $purchase_data['post_data']['edd_ezdefi_coin'] );

        if( empty( $coin_id ) ) {
            edd_set_error( 'missing_ezdefi_coin', __( 'Please select cryptocurrency', 'edd-ezdefi' ) );
            edd_send_back_to_checkout( '?payment-mode=ezdefi' );
        }

        $website_coins = $this->api->get_website_coins();

        $coin_data = null;

        foreach ( $website_coins as $key => $coin ) {
            if ( $coin['_id'] == $coin_id ) {
                $coin_data = $website_coins[$key];
            }
        }

	    if( is_null( $coin_data ) ) {
		    edd_send_back_to_checkout( '?payment-mode=ezdefi' );
	    }

	    $edd_payment_id = $this->create_edd_payment($purchase_data);

	    if( ! $edd_payment_id ) {
		    edd_send_back_to_checkout( '?payment-mode=ezdefi' );
	    }

	    $edd_payment = edd_get_payment( $edd_payment_id );

	    $edd_payment->update_meta( '_edd_ezdefi_coin', $coin_id );
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
	        edd_send_back_to_checkout( '?payment-mode=ezdefi' );
        }

        return $payment_id;
    }

    /** Gateway callback handle */
    public function gateway_callback_handle()
    {
	    if( isset( $_GET['uoid'] ) && isset( $_GET['paymentid'] ) ) {
		    $order_id = sanitize_key( $_GET['uoid'] );
		    $paymentid = sanitize_key( $_GET['paymentid'] );

		    return $this->process_payment_callback( $order_id, $paymentid );
	    }

	    if(
		    isset( $_GET['value'] ) && isset( $_GET['explorerUrl'] ) &&
		    isset( $_GET['currency'] ) && isset( $_GET['id'] ) &&
		    isset( $_GET['decimal'] )
	    ) {
		    $value = sanitize_key( $_GET['value'] );
		    $decimal = sanitize_key( $_GET['decimal'] );
		    $value = $value / pow( 10, $decimal );
		    $explorerUrl = sanitize_text_field( $_GET['explorerUrl'] );
		    $currency = sanitize_text_field( $_GET['currency'] );
		    $id = sanitize_key( $_GET['id'] );

		    return $this->process_transaction_callback( $value, $explorerUrl, $currency, $id);
	    }
    }

    public function process_payment_callback( $edd_payment_id, $ezdefi_payment_id )
    {
	    $edd_payment_id = substr( $edd_payment_id, 0, strpos( $edd_payment_id,'-' ) );

	    $edd_payment = edd_get_payment( $edd_payment_id );

	    if( ! $edd_payment ) {
		    wp_send_json_error();
	    }

        $ezdefi_payment_data = $this->api->get_ezdefi_payment( $ezdefi_payment_id );

	    if( is_null( $ezdefi_payment_data ) ) {
	        wp_send_json_error();
        }

        $status = $ezdefi_payment_data['status'];
        $uoid = (int) edd_ezdefi_sanitize_uoid( $ezdefi_payment_data['uoid'] );
        $payment_method = edd_ezdefi_is_pay_any_wallet( $ezdefi_payment_data ) ? 'amount_id' : 'ezdefi_wallet';
        $explorer_url = $ezdefi_payment_data['explorer']['tx'] . $ezdefi_payment_data['transactionHash'];

        if( $status != 'DONE' && $status != 'EXPIRED_DONE' ) {
            wp_send_json_error();
        }

        if( $status === 'DONE' ) {
            edd_update_payment_status( $edd_payment_id, 'publish' );
            edd_insert_payment_note( $edd_payment_id, "Ezdefi Explorer URL: $explorer_url" );
            edd_empty_cart();

            if( $payment_method === 'ezdefi_wallet' ) {
                $this->db->delete_exceptions( array(
                    'order_id' => $uoid
                ) );

                wp_send_json_success();
            }
        }

        $value = ( $payment_method === 'amount_id' ) ? $ezdefi_payment_data['originValue'] : ( $ezdefi_payment_data['value'] / pow( 10, $ezdefi_payment_data['decimal'] ) );

        $this->db->update_exceptions(
            array(
                'order_id' => $uoid,
                'payment_method' => $payment_method,
            ),
            array(
                'amount_id' => edd_ezdefi_sanitize_float_value( $value ),
                'currency' => $ezdefi_payment_data['token']['symbol'],
                'status' => strtolower( $status ),
                'explorer_url' => $explorer_url
            ),
            1
        );

        $this->db->delete_exceptions( array(
            'order_id' => $uoid,
            'explorer_url' => null,
        ) );

        wp_send_json_success();
    }

    public function process_transaction_callback( $value, $explorerUrl, $currency, $id  )
    {
	    $response = $this->api->get_transaction( $id );

	    if( is_wp_error( $response ) ) {
		    wp_send_json_error();
	    }

	    $response = json_decode( $response['body'], true );

	    if( $response['code'] != 1 ) {
		    wp_send_json_error();
	    }

	    $transaction = $response['data'];

	    if( $transaction['status'] != 'ACCEPTED' ) {
		    wp_send_json_error();
	    }

	    $data = array(
		    'amount_id' => $this->sanitize_float_value( $value ),
		    'currency' => $currency,
		    'explorer_url' => $explorerUrl,
	    );

	    $this->db->add_exception( $data );

	    wp_send_json_success();
    }

	protected function sanitize_float_value( $value )
	{
		$notation = explode('E', $value);

		if(count($notation) === 2){
			$exp = abs(end($notation)) + strlen($notation[0]);
			$decimal = number_format($value, $exp);
			$value = rtrim($decimal, '.0');
		}

		return str_replace( ',', '', $value );
	}
}

new EDD_Ezdefi_Payment();
