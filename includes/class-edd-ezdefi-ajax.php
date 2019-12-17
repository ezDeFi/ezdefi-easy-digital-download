<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezdefi_Ajax
{
	protected $api;

	protected $db;

    public function __construct()
    {
	    $this->api = new EDD_Ezdefi_Api();

	    $this->db = new EDD_Ezdefi_Db();

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

	    add_action( 'wp_ajax_edd_ezdefi_get_edd_payment', array( $this, 'edd_ezdefi_get_edd_payment_ajax_callback' ) );
	    add_action( 'wp_ajax_nopriv_edd_ezdefi_get_edd_payment', array( $this, 'edd_ezdefi_get_edd_payment_ajax_callback' ) );

	    add_action( 'wp_ajax_edd_ezdefi_get_exception', array( $this, 'edd_ezdefi_get_exception_ajax_callback' ) );
	    add_action( 'wp_ajax_nopriv_edd_ezdefi_get_exception', array( $this, 'edd_ezdefi_get_exception_ajax_callback' ) );

	    add_action( 'wp_ajax_edd_ezdefi_assign_amount_id', array( $this, 'edd_ezdefi_assign_amount_id_ajax_callback' ) );
	    add_action( 'wp_ajax_nopriv_edd_ezdefi_assign_amount_id', array( $this, 'edd_ezdefi_assign_amount_id_ajax_callback' ) );

	    add_action( 'wp_ajax_edd_ezdefi_reverse_edd_payment', array( $this, 'edd_ezdefi_reverse_edd_payment_ajax_callback' ) );
	    add_action( 'wp_ajax_nopriv_edd_ezdefi_reverse_edd_payment', array( $this, 'edd_ezdefi_reverse_edd_payment_ajax_callback' ) );

	    add_action( 'wp_ajax_edd_ezdefi_delete_amount_id', array( $this, 'edd_ezdefi_delete_amount_id_ajax_callback' ) );
	    add_action( 'wp_ajax_nopriv_edd_ezdefi_delete_amount_id', array( $this, 'edd_ezdefi_delete_amount_id_ajax_callback' ) );
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

		$uoid = substr( $ezdefi_payment['uoid'], 0, strpos( $ezdefi_payment['uoid'],'-' ) );

		$order = edd_get_payment( $uoid );

		if( ! $order ) {
			wp_send_json_error( __( 'Can not get payment', 'edd-ezdefi' ) );
        }

		$html = $this->generate_payment_html( $ezdefi_payment, $order );

		wp_send_json_success( $html );
	}

	private function create_ezdefi_payment( $order, $symbol, $method, $clear_meta_data = false )
	{
	    $currency_data = $this->db->get_currency_option( $symbol );

	    if( ! $currency_data ) {
	        wp_send_json_error( __( 'Can not create payment', 'edd-ezdefi' ) );
        }

		$amount_id = ( $method === 'amount_id' ) ? true : false;

		$response = $this->api->create_ezdefi_payment( $order, $currency_data, $amount_id );

		if( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message( 'create_ezdefi_payment' );
			wp_send_json_error( $error_message );
		}

		$response = json_decode( $response['body'], true );

		$payment = $response['data'];

		$data = array(
			'amount_id' => $payment['value'] / pow( 10, $payment['decimal'] ),
			'currency' => $symbol,
			'order_id' => substr( $payment['uoid'], 0, strpos( $payment['uoid'],'-' ) ),
			'status' => 'not_paid',
			'payment_method' => ( $amount_id ) ? 'amount_id' : 'ezdefi_wallet'
		);

		$this->db->add_exception( $data );

		$html = $this->generate_payment_html( $payment, $order );

		if( $clear_meta_data ) {
			$ezdefi_payment = array();
		} else {
			$ezdefi_payment = ( $order->get_meta( '_edd_ezdefi_payment' ) ) ? $order->get_meta( '_edd_ezdefi_payment' ) : array();
		}

		$ezdefi_payment[$method] = $payment['_id'];

		$order->update_meta( '_edd_ezdefi_payment', $ezdefi_payment );
		$order->update_meta( '_edd_ezdefi_currency', $symbol );
		$order->update_meta( '_edd_ezdefi_amount_id', $payment['originValue'] );
		$order->save();

		wp_send_json_success( $html );
	}

	public function generate_payment_html( $payment, $order ) {
        $total = $order->total;
        $discount = $this->db->get_currency_option( $payment['currency'] )['discount'];
        $total = $total - ( $total * ( $discount / 100 ) );
		ob_start(); ?>
		<div class="ezdefi-payment">
			<?php if( ! $payment ) : ?>
				<span><?php echo __( 'Can not get payment', 'woocommerce-gateway-ezdefi' ); ?></span>
			<?php else : ?>
				<p class="exchange">
					<span><?php echo $order->currency; ?> <?php echo $total; ?></span>
					<img width="16" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAQAAAAAYLlVAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAAAmJLR0QAAKqNIzIAAAAJcEhZcwAADsQAAA7EAZUrDhsAAAAHdElNRQfjChgQMyxZjA7+AAACP0lEQVRo3u2YvWsUQRTAf8nFQs5LCEY0aCGIB1ErRVMoFpYGTGNlo2AnBxHlrLQJKVSwiV//gqCV4gemEGJhiBYXRAtBDIhICiUGL8GP3Fjs7rs5vN0o5M1LsW+a2XkDv9/MvF12t4B2dDDODqbVOan46zgaVKzwN3A4O4VuarGAo8EZC4VeXnoKJruQK+QKa12hI2VyFyUFhY08Ymfcd1S49feU7VSZ5DPL4qrXGpxuhW/iJj8DgJutTrGJ38vHoPCobUnwg9QN8HeTItzGNP2yF7M85D11lTvhLAPSn2CYpah7R5zmOUmnChrgsrf6p6xPhvfRiAe/slsNnoqHcRketsDDbDw8ZYPvlsR5CzwMSGpICT+WhYdBSR4Ov3p9gbGV8Hr3PEAPx6XvPXZC7sBm3qSvPoRApJCB71KB+jHHERbab34YAZjLSuoW4T+EuYBNHJXC32W+A2taYAN9lgJFHjDZfGsNHUWe4XC8VVHwirD9hBLPZcpM+mN0NQTaHUGR+xySq3vpj1Gd8FfvuKjCyDiC5OyjdklpkSeE0N+aCLF6gNGY8IuCBb4zfklxzFjg4ZRQRi3wB/guB1AOjV9HhUXh3Ibo87zEYw7KpFqUWPUoUWaIrXL9gf18iRSeGPyamGdPYlI2wL/zflPQx4+g8CWu0tN6OiNBwL/5xAQjXhWQFCFc4IqMvOYY3xSKcIHlrPQ5z/UVvSr3wQqRK+QKuYIfVU9hSuGt+L924ZoFvqmgji+kZl6wSI2qtsAfm/EoPAbFFD0AAAAldEVYdGRhdGU6Y3JlYXRlADIwMTktMTAtMjRUMTY6NTE6NDQrMDA6MDBiAik3AAAAJXRFWHRkYXRlOm1vZGlmeQAyMDE5LTEwLTI0VDE2OjUxOjQ0KzAwOjAwE1+RiwAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAAASUVORK5CYII=" />
					<span><?php echo ( $payment['value'] / pow( 10, $payment['decimal'] ) ); ?> <?php echo $payment['currency']; ?></span>
				</p>
				<p><?php _e( 'You have', 'edd-ezdefi' ); ?> <span class="count-down" data-endtime="<?php echo $payment['expiredTime']; ?>"></span> <?php _e( 'to scan this QR Code', 'edd-ezdefi' ); ?></p>
				<p>
					<a class="qrcode <?php echo (time() > strtotime($payment['expiredTime'])) ? 'expired' : ''; ?>" href="<?php echo $payment['deepLink']; ?>">
						<img src="<?php echo $payment['qr']; ?>" />
					</a>
				</p>
				<?php if( isset( $payment['amountId'] ) && $payment['amountId'] === true ) : ?>
                    <p class="receive-address">
                        <strong><?php _e( 'Address', 'woocommerce-gateway-ezdefi' ); ?>:</strong>
                        <span class="copy-to-clipboard" title="Copy to clipboard">
                            <span class="copy-content"><?php echo $payment['to']; ?></span>
                            <img src="<?php echo edd_ezdefi()->plugin_url() . '/assets/copy-icon.svg'; ?>" />
                        </span>
                    </p>
                    <p class="payment-amount">
                        <strong><?php _e( 'Amount', 'woocommerce-gateway-ezdefi' ); ?>:</strong>
                        <span class="copy-to-clipboard" title="Copy to clipboard">
                            <span class="copy-content"><?php echo $payment['originValue']; ?></span>
                            <span class="amount"><?php echo $payment['token']['symbol'] ?></span>
                            <img src="<?php echo edd_ezdefi()->plugin_url() . '/assets/copy-icon.svg'; ?>" />
                        </span>
                    </p>
                    <p class="note">
						<?php _e( 'You have to pay exact amount so that your order can be handle property.', 'woocommerce-gateway-ezdefi' ); ?><br/>
                    </p>
                    <p class="note">
						<?php _e( 'If you have difficulty for sending exact amount, try to use', 'woocommerce-gateway-ezdefi' ); ?> <a href="" class="ezdefiEnableBtn">ezDeFi Wallet</a>
                    </p>
				<?php else : ?>
                    <p class="app-link-list">
                        <a href=""><img src="<?php echo plugins_url( 'assets/android-icon.png', EDD_EZDEFI_MAIN_FILE ); ?>" /><?php _e( 'Download ezDefi for IOS', 'edd-ezdefi' ); ?></a>
                        <a href=""><img src="<?php echo plugins_url( 'assets/ios-icon.png', EDD_EZDEFI_MAIN_FILE ); ?>" /><?php _e( 'Download ezDefi for Android', 'edd-ezdefi' ); ?></a>
                    </p>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php return ob_get_clean();
	}

	public function edd_ezdefi_get_exception_ajax_callback()
	{
		$offset = 0;

		$per_page = 15;

		if( isset( $_POST['page'] ) && $_POST['page'] > 1 ) {
			$offset = $per_page * ( $_POST['page'] - 1 );
		}

		$data = $this->db->get_exception( $_POST, $offset, $per_page );

		$total = $this->db->get_exception_total()[0]->total;
		$total_pages = ceil($total / $per_page );

		$response = array(
			'data' => $data,
			'meta_data' => array(
				'current_page' => ( isset( $_POST['page'] ) ) ? (int) $_POST['page'] : 1 ,
				'total' => (int) $total,
				'total_pages' => $total_pages
			)
		);

		wp_send_json_success( $response );
	}

	public function edd_ezdefi_get_edd_payment_ajax_callback()
	{
		$args = array(
			'status' => 'pending',
			'output' => 'payments'
		);

		$payments = edd_get_payments( $args );

		$data = array();

		foreach ($payments as $payment) {
			$data[] = array(
				'id' => $payment->ID,
				'total' => $payment->total,
				'currency' => $payment->currency,
				'billing_email' => $payment->email,
				'amount_id' => $payment->get_meta( '_edd_ezdefi_amount_id' ),
				'token' => $payment->get_meta( '_edd_ezdefi_currency' ),
				'date_created' => date('Y-m-d H:i:s', strtotime( $payment->date ) )
			);
		}

		wp_send_json_success( $data );
	}

	public function edd_ezdefi_assign_amount_id_ajax_callback()
	{
		if( ! isset( $_POST['amount_id'] ) || ! isset( $_POST['order_id'] ) || ! isset( $_POST['currency'] ) ) {
            wp_send_json_error();
        }

		$amount_id = $_POST['amount_id'];

		$currency = $_POST['currency'];

		$old_order_id = ( $_POST['old_order_id'] && ! empty( $_POST['old_order_id'] ) ) ? $_POST['old_order_id'] : null;

		$order_id = $_POST['order_id'];

		$edd_payment = edd_get_payment( $order_id );

		if( ! $edd_payment ) {
			wp_send_json_error();
		}

		$this->db->delete_amount_id_exception( $amount_id, $currency, $old_order_id );

		edd_update_payment_status( $order_id, 'publish' );

		wp_send_json_success();
	}

	public function edd_ezdefi_reverse_edd_payment_ajax_callback()
	{
		if( ! isset( $_POST['amount_id'] ) || ! isset( $_POST['order_id'] ) || ! isset( $_POST['currency'] ) ) {
			wp_send_json_error();
		}

		$amount_id = $_POST['amount_id'];

		$currency = $_POST['currency'];

		$order_id = $_POST['order_id'];

		$edd_payment = edd_get_payment( $order_id );

		if( ! $edd_payment ) {
			wp_send_json_error();
		}

		$this->db->delete_amount_id_exception( $amount_id, $currency, $order_id );

		edd_update_payment_status( $order_id, 'pending' );

		wp_send_json_success();
	}

	public function edd_ezdefi_delete_amount_id_ajax_callback()
	{
		$amount_id = $_POST['amount_id'];

		$currency = $_POST['currency'];

		$order_id = $_POST['order_id'];

	    $this->db->delete_amount_id_exception( $amount_id, $currency, $order_id );
	}
}

new EDD_Ezdefi_Ajax();