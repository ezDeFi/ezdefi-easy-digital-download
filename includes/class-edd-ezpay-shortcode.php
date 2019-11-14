<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezpay_Shortcode
{
    public function __construct() {
	    add_filter( 'do_shortcode_tag', array( $this, 'prepend_content_to_shortcode' ), 10, 4 );
    }

    public function prepend_content_to_shortcode( $output, $tag )
    {
        global $edd_receipt_args;

        if ( $tag != 'edd_receipt' ) {
            return $output;
        }

        $payment = edd_get_payment( $edd_receipt_args['id'] );

        if( empty( $payment ) ) {
            return $output;
        }

        if( empty( $payment->get_meta( '_edd_ezpay_payment' ) || empty( $payment->get_meta( '_edd_ezpay_currency' ) ) ) ) {
            return $output;
        }

        $status = edd_get_payment_status( $payment, true );

        if( strtolower( $status ) === 'complete' ) {
            return $output;
        }

        $data = array(
            'uoid' => edd_get_payment_number( $payment->ID ),
            'paymentid' => $payment->get_meta( '_edd_ezpay_payment' )
        );

        $ezpay_currency = edd_ezpay_get_currency();

	    $symbol = $payment->get_meta( '_edd_ezpay_currency' );
	    $index = array_search( $symbol, array_column( $ezpay_currency, 'symbol' ) );
	    $selected_currency = $ezpay_currency[$index];

        $this->enqueue_scripts();

        ob_start(); ?>
        <div id="edd-ezpay-qrcode-section">
            <script type="application/json" id="payment-data"><?php echo json_encode( $data ); ?></script>
            <div class="selected-currency">
                <div class="left">
                    <div class="logo">
                        <img class="logo" src="<?php echo $selected_currency['logo']; ?>" alt="">
                    </div>
                    <div class="text">
                        <span class="symbol"><?php echo $selected_currency['symbol']; ?></span>/<span class="name"><?php echo $selected_currency['name']; ?></span><br/>
                        <span class="desc"><?php echo $selected_currency['desc']; ?></span>
                    </div>
                </div>
                <div>
                    <a href="" class="changeBtn">Change</a>
                </div>
            </div>
            <div class="currency-select">
                <?php foreach ($ezpay_currency as $c) : ?>
                    <div class="currency-item">
                        <input <?php echo ($c['symbol'] === $selected_currency['symbol']) ? 'checked' : ''; ?> type="radio" name="currency" id="<?php echo $c['symbol']; ?>">
                        <label for="<?php echo $c['symbol']; ?>">
                            <div class="left">
                                <img class="logo" src="<?php echo $c['logo']; ?>" alt="">
                                <span class="symbol"><?php echo $c['symbol']; ?></span>
                            </div>
                            <div class="right">
                                <span class="name"><?php echo $c['name']; ?></span>
                                <span class="discount">Discount: <?php echo ( intval($c['discount']) > 0) ? $c['discount'] : 0; ?>%</span>
                                <span class="more">
                                    <?php if( isset($c['desc']) && $c['desc'] != '') : ?>
                                        <span class="tooltip desc"><?php echo $c['desc']; ?></span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="ezpay-payment-tabs">
                <ul>
                    <?php $ezpay_method = edd_get_option( 'ezpay_method' ); ?>
			        <?php if( $ezpay_method === 'all' || $ezpay_method === 'amount_id' ) : ?>
                        <li><a href="#amount_id">Order identification method</a></li>
			        <?php endif; ?>
			        <?php if( $ezpay_method === 'all' || $ezpay_method === 'ezpay_wallet' ) : ?>
                        <li><a href="#ezpay_wallet">Used ezPay wallet</a></li>
			        <?php endif; ?>
                </ul>
		        <?php if( $ezpay_method === 'all' || $ezpay_method === 'amount_id' ) : ?>
                    <div id="amount_id" data-method="amount_id" class="ezpay-payment-panel">abcascsa</div>
		        <?php endif; ?>
		        <?php if( $ezpay_method === 'all' || $ezpay_method === 'ezpay_wallet' ) : ?>
                    <div id="ezpay_wallet" data-method="ezpay_wallet" class="ezpay-payment-panel">fsafsfdsfdsf</div>
		        <?php endif; ?>
            </div>
            <div class="ezpay-payment"></div>
            <button class="submitBtn" style="display: none">Confirm</button>
        </div>
        <?php
        $prepend = ob_get_contents();
        ob_end_clean();

        $output = $prepend . $output;

        return $output;
    }

    /** Load needed CSS and JS file */
    public function enqueue_scripts()
    {
	    wp_enqueue_style( 'edd_ezpay_blockui', plugins_url( 'assets/js/jquery.blockUI.js', WC_EZPAY_MAIN_FILE ), array( 'jquery' ), WC_EZPAY_VERSION );
	    wp_enqueue_style( 'edd_ezpay_checkout', EDD_EZPay()->plugin_url() . '/assets/edd-ezpay-qrcode.css' );
        wp_enqueue_script( 'edd_ezpay_checkout', EDD_EZPay()->plugin_url() . '/assets/edd-ezpay-qrcode.js', array( 'jquery', 'jquery-ui-tabs' ), '', true );
        wp_localize_script(
            'edd_ezpay_checkout',
            'edd_ezpay_data',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'checkout_url' => wc_get_checkout_url()
            )
        );
    }
}

new EDD_Ezpay_Shortcode();