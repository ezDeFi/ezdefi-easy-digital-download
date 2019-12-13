<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezdefi_Payment
{
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
	    if( ! isset( $_GET['uoid'] ) || ! isset( $_GET['paymentid'] ) ) {
		    wp_die();
	    }

        $edd_payment_id = $_GET['uoid'];

	    $edd_payment_id = substr( $edd_payment_id, 0, strpos( $edd_payment_id,'-' ) );

	    $edd_payment = edd_get_payment( $edd_payment_id );

	    if( ! $edd_payment ) {
	    	wp_die();
	    }

        $ezdefi_payment_id = $_GET['paymentid'];

        $response = $this->api->get_ezdefi_payment( $ezdefi_payment_id );

        if( is_wp_error( $response ) ) {
            wp_die();
        }

        $ezdefi_payment_data = json_decode( $response['body'], true );

	    if( $ezdefi_payment_data['code'] < 0 ) {
		    wp_die();
	    }

	    $ezdefi_payment_data = $ezdefi_payment_data['data'];

        $status = $ezdefi_payment_data['status'];

        $amount_id = $ezdefi_payment_data['value'] / pow( 10, $ezdefi_payment_data['decimal'] );

        $currency = $ezdefi_payment_data['currency'];

	    if( ! isset ( $payment['amount_id'] ) ) {
		    $amount_id = round( $amount_id, 10 );
	    }

        if( $status === 'DONE' ) {
	        edd_update_payment_status( $edd_payment_id, 'publish' );
	        edd_empty_cart();
	        $this->db->update_exception_status( $amount_id, $currency, $edd_payment_id, strtolower($status) );
        } elseif( $status === 'EXPIRED_DONE' ) {
	        $this->db->update_exception_status( $amount_id, $currency, $edd_payment_id, strtolower($status) );
        }

	    wp_die();
    }
}

new EDD_Ezdefi_Payment();
