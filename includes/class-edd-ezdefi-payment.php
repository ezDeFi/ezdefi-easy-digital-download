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
	    $symbol = $purchase_data['post_data']['edd_ezdefi_currency_symbol'];

	    $currency_data = $this->find_currency_data( $symbol );

	    if( $currency_data === false ) {
		    edd_send_back_to_checkout( '?payment-mode=ezdefi' );
	    }

	    $edd_payment_id = $this->create_edd_payment($purchase_data);

	    if( ! $edd_payment_id ) {
		    edd_send_back_to_checkout( '?payment-mode=ezdefi' );
	    }

	    $edd_payment = edd_get_payment( $edd_payment_id );

	    $edd_payment->update_meta( '_edd_ezdefi_currency', $symbol );
	    $edd_payment->save();

        edd_send_to_success_page();
    }

	private function find_currency_data( $symbol ) {
		$currency = edd_get_option( 'ezdefi_currency' );

		$index = array_search( $symbol, array_column( $currency, 'symbol' ) );

		if( $index === false ) {
			return false;
		}

		return $currency[$index];
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

	    $response = $this->api->get_ezdefi_payment( $ezdefi_payment_id );

	    if( is_wp_error( $response ) ) {
		    wp_send_json_error();
	    }

	    $ezdefi_payment_data = json_decode( $response['body'], true );

	    if( $ezdefi_payment_data['code'] < 0 ) {
		    wp_send_json_error();
	    }

	    $ezdefi_payment_data = $ezdefi_payment_data['data'];

	    $status = $ezdefi_payment_data['status'];

	    if( $status === 'PENDING' || $status === 'EXPIRED' ) {
	    	wp_send_json_error();
	    }

	    if( ( isset( $ezdefi_payment_data['amountId'] ) && $ezdefi_payment_data['amountId'] === true ) ) {
		    $amount_id = $ezdefi_payment_data['originValue'];
	    } else {
		    $amount_id = $ezdefi_payment_data['value'] / pow( 10, $ezdefi_payment_data['decimal'] );
	    }

	    $amount_id = number_format( $amount_id, 12 );

	    $currency = $ezdefi_payment_data['currency'];

	    $exception_data = array(
		    'status' => strtolower($status),
		    'explorer_url' => (string) self::EXPLORER_URL . $ezdefi_payment_data['transactionHash']
	    );

	    $wheres = array(
		    'amount_id' => $amount_id,
		    'currency' => (string) $currency,
		    'order_id' => (int) $edd_payment_id
	    );

	    if( isset( $ezdefi_payment_data['amountId'] ) && $ezdefi_payment_data['amountId'] = true ) {
		    $wheres['payment_method'] = 'amount_id';
	    } else {
		    $wheres['payment_method'] = 'ezdefi_wallet';
	    }

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
		    'amount_id' => number_format( $value, 12 ),
		    'currency' => $currency,
		    'explorer_url' => $explorerUrl,
	    );

	    $this->db->add_exception( $data );

	    wp_send_json_success();
    }
}

new EDD_Ezdefi_Payment();
