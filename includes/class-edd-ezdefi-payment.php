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

	    if( $status === 'PENDING' || $status === 'EXPIRED' ) {
	    	wp_send_json_error();
	    }

	    if( ( isset( $ezdefi_payment_data['amountId'] ) && $ezdefi_payment_data['amountId'] === true ) ) {
            $payment_method = 'amount_id';
            $amount_id = $ezdefi_payment_data['originValue'];
	    } else {
            $payment_method = 'ezdefi_wallet';
		    $amount_id = $ezdefi_payment_data['value'] / pow( 10, $ezdefi_payment_data['decimal'] );
	    }

        $amount_id = $this->sanitize_float_value( $amount_id );
        $amount_id = str_replace( ',', '', $amount_id );

	    $exception_data = array(
		    'status' => strtolower($status),
		    'explorer_url' => (string) self::EXPLORER_URL . $ezdefi_payment_data['transactionHash']
	    );

	    $wheres = array(
		    'amount_id' => $amount_id,
		    'currency' => (string) $ezdefi_payment_data['currency'],
		    'order_id' => (int) $edd_payment_id,
            'payment_method' => $payment_method
	    );

	    if( $status === 'DONE' ) {
		    edd_update_payment_status( $edd_payment_id, 'publish' );
		    edd_empty_cart();
		    $this->db->update_exception( $wheres, $exception_data );

		    if( ! isset( $ezdefi_payment_data['amountId'] ) || ( isset( $ezdefi_payment_data['amountId'] ) && $ezdefi_payment_data['amountId'] != true ) ) {
			    $this->db->delete_exception_by_order_id( $wheres['order_id'] );
		    }
	    } elseif( $status === 'EXPIRED_DONE' ) {
		    $this->db->update_exception( $wheres, $exception_data );
	    }

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
