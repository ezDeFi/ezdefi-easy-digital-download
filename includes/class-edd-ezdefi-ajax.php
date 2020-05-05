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

	    add_action( 'wp_ajax_edd_ezdefi_check_api_key', array( $this, 'edd_ezdefi_check_api_key_ajax_callback' ) );
	    add_action( 'wp_ajax_nopriv_edd_ezdefi_check_api_key', array( $this, 'edd_ezdefi_check_api_key_ajax_callback' ) );

        add_action( 'wp_ajax_edd_ezdefi_check_public_key', array( $this, 'edd_ezdefi_check_public_key_ajax_callback' ) );
        add_action( 'wp_ajax_nopriv_edd_ezdefi_check_public_key', array( $this, 'edd_ezdefi_check_public_key_ajax_callback' ) );

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

	/**
	 * Check API Key Ajax Callback
	 */
	public function edd_ezdefi_check_api_key_ajax_callback()
    {
	    if( ! isset( $_POST['api_url'] ) || ! isset( $_POST['api_key'] ) ) {
		    wp_die('false');
	    }

	    $api_url = sanitize_text_field( $_POST['api_url'] );
	    $api_key = sanitize_text_field( $_POST['api_key'] );

	    $api = new EDD_Ezdefi_Api( $api_url, $api_key );

	    $response = $api->check_api_key();

	    if( is_wp_error( $response ) ) {
		    wp_die('false');
	    }

	    $response = json_decode( $response['body'], true );

	    if( $response['code'] != 1 ) {
		    wp_die('false');
	    }

	    wp_die('true');
    }

    public function edd_ezdefi_check_public_key_ajax_callback()
    {
        if( ! isset( $_POST['api_url'] ) || ! isset( $_POST['api_key'] ) || ! isset( $_POST['public_key'] ) ) {
            wp_die('false');
        }

        $api_url = sanitize_text_field( $_POST['api_url'] );
        $api_key = sanitize_text_field( $_POST['api_key'] );

        $api = new EDD_Ezdefi_Api( $api_url, $api_key );
        $api->set_public_key( $_POST['public_key'] );

        $response = $api->get_website_config();

        if( is_null( $response ) ) {
            wp_die('false');
        }

        wp_die('true');
    }

    /** AJAX callback to check edd payment status */
    public function edd_ezdefi_check_payment_status_ajax_callback()
    {
	    if( ! isset( $_POST['paymentId'] ) ) {
		    wp_die();
	    }

        $payment_id = sanitize_key( $_POST['paymentId'] );

        $payment_status = edd_get_payment_status( $payment_id, true );

        wp_die($payment_status);
    }

    public function edd_ezdefi_create_payment_ajax_callback()
    {
	    $message = __( 'Can not create payment', 'edd-ezdefi' );

        $params = array(
            'uoid' => '',
            'coin_id' => '',
            'method' => '',
        );

        $params = array_merge( $params, $_POST );

        foreach ($params as $param) {
            if( empty( $param ) ) {
                wp_send_json_error( $message );
            }
        }

        $website_coins = $this->api->get_website_coins();

        if( is_null( $website_coins ) ) {
            wp_send_json_error( $message );
        }

        $coin_id = sanitize_text_field( $params['coin_id'] );

        $coin_data = array();

        foreach( $website_coins as $website_coin ) {
            if( $website_coin['_id'] === $coin_id ) {
                $coin_data = $website_coin;
                break;
            }
        }

        if( empty( $coin_data ) ) {
            wp_send_json_error( $message );
        }


        $uoid = sanitize_key( $params['uoid'] );

        $order = edd_get_payment( $uoid );

        if( ! $order ) {
            wp_send_json_error( $message );
        }

        $method = sanitize_text_field( $params['method'] );

        $accepted_methods = array( 'ezdefi_wallet', 'amount_id' );

        if( ! in_array( $method, $accepted_methods ) ) {
            wp_send_json_error( $message );
        }

        $amount_id = ( $method === 'amount_id' ) ? true : false;

        $payment = $this->api->create_ezdefi_payment( $order, $coin_data, $amount_id );

        if( is_null( $payment ) ) {
            wp_send_json_error();
        }

        if( $amount_id ) {
            $value = $payment['originValue'];
            $value = explode( '.', $value );
            $number = $value[0];
            $decimal = $value[1];
            $decimal = substr( $decimal, 0, $coin_data['decimal'] );
            $value = "$number" . '.' . "$decimal";
            $payment['originValue'] = $value;
        } else {
            $value = $payment['value'] / pow( 10, $payment['decimal'] );
        }

        $data = array(
            'amount_id' => str_replace( ',', '', $value),
            'currency' => $coin_data['token']['symbol'],
            'order_id' => edd_ezdefi_sanitize_uoid( $payment['uoid'] ),
            'status' => 'not_paid',
            'payment_method' => ( $amount_id ) ? 'amount_id' : 'ezdefi_wallet'
        );

        $this->db->add_exception( $data );

        $html = $this->generate_payment_html( $payment, $order, $coin_data );

        $order->update_meta( '_edd_ezdefi_coin', $coin_data['_id'] );
        $order->save();

        wp_send_json_success( $html );
    }

	public function generate_payment_html( $payment, $order, $coin_data ) {
        $total = $order->total;
        $discount = $coin_data['discount'];
        $total = $total * ( number_format( ( 100 - $discount ) / 100, 8 ) );
		ob_start(); ?>
		<div class="ezdefi-payment">
			<?php if( ! $payment ) : ?>
				<span><?php echo __( 'Can not get payment', 'edd-ezdefi' ); ?></span>
			<?php else : ?>
                <?php
                    if( ( isset( $payment['amountId'] ) && $payment['amountId'] === true ) ) {
                        $value = $payment['originValue'];
                    } else {
                        $value = $payment['value'] / pow( 10, $payment['decimal'] );
                    }

                    $notation = explode('E', strtoupper( $value ));

                    if(count($notation) === 2){
                        $exp = abs(end($notation)) + strlen($notation[0]);
                        $decimal = number_format($value, $exp);
                        $value = rtrim($decimal, '.0');
                    }

                    $value = rtrim($value, '.');
                ?>
				<p class="exchange">
					<span><?php echo edd_ezdefi_sanitize_float_value( $total ); ?> <?php echo $order->currency; ?></span>
					<img width="16" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAQAAAAAYLlVAAAABGdBTUEAALGPC/xhBQAAACBjSFJNAAB6JgAAgIQAAPoAAACA6AAAdTAAAOpgAAA6mAAAF3CculE8AAAAAmJLR0QAAKqNIzIAAAAJcEhZcwAADsQAAA7EAZUrDhsAAAAHdElNRQfjChgQMyxZjA7+AAACP0lEQVRo3u2YvWsUQRTAf8nFQs5LCEY0aCGIB1ErRVMoFpYGTGNlo2AnBxHlrLQJKVSwiV//gqCV4gemEGJhiBYXRAtBDIhICiUGL8GP3Fjs7rs5vN0o5M1LsW+a2XkDv9/MvF12t4B2dDDODqbVOan46zgaVKzwN3A4O4VuarGAo8EZC4VeXnoKJruQK+QKa12hI2VyFyUFhY08Ymfcd1S49feU7VSZ5DPL4qrXGpxuhW/iJj8DgJutTrGJ38vHoPCobUnwg9QN8HeTItzGNP2yF7M85D11lTvhLAPSn2CYpah7R5zmOUmnChrgsrf6p6xPhvfRiAe/slsNnoqHcRketsDDbDw8ZYPvlsR5CzwMSGpICT+WhYdBSR4Ov3p9gbGV8Hr3PEAPx6XvPXZC7sBm3qSvPoRApJCB71KB+jHHERbab34YAZjLSuoW4T+EuYBNHJXC32W+A2taYAN9lgJFHjDZfGsNHUWe4XC8VVHwirD9hBLPZcpM+mN0NQTaHUGR+xySq3vpj1Gd8FfvuKjCyDiC5OyjdklpkSeE0N+aCLF6gNGY8IuCBb4zfklxzFjg4ZRQRi3wB/guB1AOjV9HhUXh3Ibo87zEYw7KpFqUWPUoUWaIrXL9gf18iRSeGPyamGdPYlI2wL/zflPQx4+g8CWu0tN6OiNBwL/5xAQjXhWQFCFc4IqMvOYY3xSKcIHlrPQ5z/UVvSr3wQqRK+QKuYIfVU9hSuGt+L924ZoFvqmgji+kZl6wSI2qtsAfm/EoPAbFFD0AAAAldEVYdGRhdGU6Y3JlYXRlADIwMTktMTAtMjRUMTY6NTE6NDQrMDA6MDBiAik3AAAAJXRFWHRkYXRlOm1vZGlmeQAyMDE5LTEwLTI0VDE2OjUxOjQ0KzAwOjAwE1+RiwAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAAASUVORK5CYII=" />
					<span><?php echo $value . ' ' . $payment['currency']; ?></span>
				</p>
				<p><?php _e( 'You have', 'edd-ezdefi' ); ?> <span class="count-down" data-endtime="<?php echo $payment['expiredTime']; ?>"></span> <?php _e( 'to scan this QR Code', 'edd-ezdefi' ); ?></p>
				<p>
					<?php
                        if( isset( $payment['amountId'] ) && $payment['amountId'] === true ) {
                            $deepLink = $payment['deepLink'];
                        } else {
                            $deepLink = 'ezdefi://' . $payment['deepLink'];
                        }
					?>
                    <a class="qrcode <?php echo (time() > strtotime($payment['expiredTime'])) ? 'expired' : ''; ?>" href="<?php echo $deepLink; ?>" target="_blank">
                        <img class="main" src="<?php echo $payment['qr']; ?>" />
	                    <?php if( isset( $payment['amountId'] ) && $payment['amountId'] === true ) : ?>
		                    <img class="alt" style="display: none" src="<?php echo 'https://chart.googleapis.com/chart?cht=qr&chl='.$payment['to'].'&chs=200x200&chld=L|0'; ?>" alt="">
	                    <?php endif; ?>
                    </a>
				</p>
				<?php if( isset( $payment['amountId'] ) && $payment['amountId'] === true ) : ?>
                    <p class="receive-address">
                        <strong><?php _e( 'Address', 'edd-ezdefi' ); ?>:</strong>
                        <span class="copy-to-clipboard" data-clipboard-text="<?php echo $payment['to']; ?>" title="Copy to clipboard">
                            <span class="copy-content"><?php echo $payment['to']; ?></span>
                            <img src="<?php echo edd_ezdefi()->plugin_url() . '/assets/copy-icon.svg'; ?>" />
                        </span>
                    </p>

                    <p class="payment-amount">
                        <strong><?php _e( 'Amount', 'edd-ezdefi' ); ?>:</strong>
                        <span class="copy-to-clipboard" data-clipboard-text="<?php echo $value; ?>" title="Copy to clipboard">
                            <span class="copy-content"><?php echo $value; ?></span>
                            <span class="amount"><?php echo $payment['token']['symbol'] ?></span>
                            <img src="<?php echo edd_ezdefi()->plugin_url() . '/assets/copy-icon.svg'; ?>" />
                        </span>
                    </p>

					<div class="qrcode__info--main">
						<p class="note">
							<?php _e( 'If you get error when scanning this QR Code, please use', 'edd-ezdefi' ); ?>
							<a href="" class="changeQrcodeBtn"><?php _e( 'alternative QR Code', 'edd-ezdefi' ); ?></a>
						</p>
					</div>

					<div class="qrcode__info--alt" style="display: none">
	                    <p class="note">
							<?php _e( 'You have to pay exact amount so that your order can be handled properly.', 'edd-ezdefi' ); ?><br/>
	                    </p>
	                    <p class="note">
							<?php _e( 'If you have difficulty for sending exact amount, try', 'edd-ezdefi' ); ?> <a href="" class="ezdefiEnableBtn">ezDeFi Wallet</a>
	                    </p>
                        <p class="changeQrcode">
                            <a class="changeQrcodeBtn" href="">Use original QR Code</a>
                        </p>
					</div>
				<?php else : ?>
                    <p class="app-link-list">
                        <a target="_blank" href="https://ezdefi.com/ios?utm=edd-download"><img src="<?php echo plugins_url( 'assets/ios-icon.png', EDD_EZDEFI_MAIN_FILE ); ?>" /></a>
                        <a target="_blank" href="https://ezdefi.com/android?utm=edd-download"><img src="<?php echo plugins_url( 'assets/android-icon.png', EDD_EZDEFI_MAIN_FILE ); ?>" /></a>
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
			$page = sanitize_key( $_POST['page'] );
			$offset = $per_page * ( $page - 1 );
		}

		$post_data = array_map( 'sanitize_text_field', $_POST );

		$data = $this->db->get_exceptions( $post_data, $offset, $per_page );

		$total = $data['total'];

		$total_pages = ceil($total / $per_page );

		$response = array(
			'data' => $data['data'],
			'meta_data' => array(
				'current_page' => ( isset( $_POST['page'] ) ) ? (int) sanitize_key( $_POST['page'] ) : 1 ,
				'total' => (int) $total,
				'total_pages' => $total_pages,
                'offset' => $offset
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

		if( isset( $_POST['keyword'] ) && ! empty( $_POST['keyword'] ) ) {
			$args['p'] = sanitize_text_field( $_POST['keyword'] );
		}

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
        if( ! isset( $_POST['order_id'] ) || ! isset( $_POST['exception_id'] ) ) {
            wp_send_json_error();
        }

        $exception_id = sanitize_key( $_POST['exception_id'] );

        $old_order_id = ( isset( $_POST['old_order_id'] ) && ! empty( $_POST['old_order_id'] ) ) ? sanitize_key( $_POST['old_order_id'] ) : null;

		$order_id = sanitize_key( $_POST['order_id'] );

		$edd_payment = edd_get_payment( $order_id );

		if( ! $edd_payment ) {
			wp_send_json_error();
		}

		edd_update_payment_status( $order_id, 'publish' );

		if( $old_order_id && $old_order_id != $order_id && edd_get_payment( $old_order_id ) ) {
            edd_update_payment_status( $old_order_id, 'pending' );
        }

        $this->db->update_exceptions(
            array( 'id' => (int) $exception_id ),
            array(
                'order_id' => $order_id,
                'confirmed' => 1
            )
        );

        $this->db->update_exceptions(
            array(
                'order_id' => $order_id,
                'explorer_url' => null
            ),
            array(
                'is_show' => 0
            )
        );

		wp_send_json_success();
	}

	public function edd_ezdefi_reverse_edd_payment_ajax_callback()
	{
        if( ! isset( $_POST['order_id'] ) || ! isset( $_POST['exception_id'] ) ) {
            wp_send_json_error();
        }

        $exception_id = sanitize_key( $_POST['exception_id'] );

		$order_id = sanitize_key( $_POST['order_id'] );

		$edd_payment = edd_get_payment( $order_id );

		if( ! $edd_payment ) {
			wp_send_json_error();
		}

		edd_update_payment_status( $order_id, 'pending' );

        $exception = $this->db->get_exception( $exception_id );

        if( is_null( $exception->explorer_url ) || empty( $exception->explorer_url ) ) {
            $data_update = array(
                'confirmed' => 0
            );
        } else {
            $data_update = array(
                'confirmed' => 0,
                'order_id' => null,
            );
        }

        $this->db->update_exceptions(
            array( 'id' => (int) $exception_id ),
            $data_update
        );

        $this->db->update_exceptions(
            array(
                'order_id' => $order_id,
                'explorer_url' => null
            ),
            array(
                'is_show' => 1
            )
        );

        wp_send_json_success();
	}

	public function edd_ezdefi_delete_amount_id_ajax_callback()
	{
        if( ! isset( $_POST['exception_id'] ) ) {
            wp_send_json_error();
        }

        $exception_id = sanitize_key( $_POST['exception_id'] );

        $this->db->delete_exception( $exception_id );

        wp_send_json_success();
	}
}

new EDD_Ezdefi_Ajax();