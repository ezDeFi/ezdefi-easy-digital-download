<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezdefi_Shortcode
{
    public $api;

    public function __construct() {
        $this->api = new EDD_Ezdefi_Api();

	    add_filter( 'do_shortcode_tag', array( $this, 'prepend_content_to_shortcode' ), 10, 4 );
    }

    public function prepend_content_to_shortcode( $output, $tag )
    {
        global $edd_receipt_args;

        if ( $tag != 'edd_receipt' ) {
            return $output;
        }

        $edd_payment = edd_get_payment( $edd_receipt_args['id'] );

        if( empty( $edd_payment ) ) {
            return $output;
        }

        if( empty( $edd_payment->get_meta( '_edd_ezdefi_currency' ) ) ) {
            return $output;
        }

        $status = edd_get_payment_status( $edd_payment, true );

        if( strtolower( $status ) === 'complete' ) {
            return $output;
        }

        $ezdefi_currency = edd_ezdefi_get_currency();

	    $symbol = $edd_payment->get_meta( '_edd_ezdefi_currency' );
	    $index = array_search( $symbol, array_column( $ezdefi_currency, 'symbol' ) );

	    if( $index === false ) {
	        return;
        }

	    $selected_currency = $ezdefi_currency[$index];

	    $payment_data = array(
		    'uoid' => edd_get_payment_number( $edd_payment->ID ),
		    'ezdefi_payment' => ( $edd_payment->get_meta( '_edd_ezdefi_payment' ) ) ? $edd_payment->get_meta( '_edd_ezdefi_payment' ) : ''
	    );

	    $payment_method = edd_get_option( 'ezdefi_method' );

        $this->enqueue_scripts();

        ob_start(); ?>
        <div id="edd-ezdefi-qrcode-section">
            <script type="application/json" id="payment-data"><?php echo json_encode( $payment_data ); ?></script>
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
                    <a href="" class="changeBtn"><?php _e( 'Change', 'edd-ezdefi' ); ?></a>
                </div>
            </div>
            <div class="currency-select">
                <?php foreach ($ezdefi_currency as $c) : ?>
                    <div class="currency-item">
                        <input <?php echo ($c['symbol'] === $selected_currency['symbol']) ? 'checked' : ''; ?> type="radio" name="currency" id="<?php echo $c['symbol']; ?>">
                        <label for="<?php echo $c['symbol']; ?>">
                            <div class="left">
                                <img class="logo" src="<?php echo $c['logo']; ?>" alt="">
                                <span class="symbol"><?php echo $c['symbol']; ?></span>
                            </div>
                            <div class="right">
                                <span class="name"><?php echo $c['name']; ?></span>
                                <span class="discount"><?php _e( 'Discount', 'edd-ezdefi' ); ?>: <?php echo ( intval($c['discount']) > 0) ? $c['discount'] : 0; ?>%</span>
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
            <div class="ezdefi-payment-tabs">
                <ul>
		            <?php
		            foreach( $payment_method as $key => $value ) {
			            echo '<li>';
			            switch ($key) {
				            case 'amount_id' :
					            echo '<a href="#'.$key.'" id="tab-'.$key.'"><span>' . __( 'Simple method', 'woocommerce-gateway-ezdefi' ) . '</span></a>';
					            break;
				            case 'ezdefi_wallet' :
					            echo '<a href="#'.$key.'" id="tab-'.$key.'" style="background-image: url('.plugins_url( 'assets/images/ezdefi-icon.png', WC_EZDEFI_MAIN_FILE ).')"><span> ' . __( 'Pay with ezDeFi wallet', 'woocommerce-gateway-ezdefi' ) . '</span></a>';
					            break;
			            }
			            echo '</a></li>';
		            }
		            ?>
                </ul>
	            <?php foreach( $payment_method as $key => $value ) : ?>
                    <div id="<?php echo $key;?>" class="ezdefi-payment-panel"></div>
	            <?php endforeach; ?>
            </div>
            <button class="submitBtn" style="display: none"<?php _e( 'Confirm', 'edd-ezdefi' ); ?></button>
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
	    wp_enqueue_style( 'edd_ezdefi_blockui', EDD_Ezdefi()->plugin_url() . '/assets/js/jquery.blockUI.js', array( 'jquery' ), '' );
	    wp_enqueue_style( 'edd_ezdefi_checkout', EDD_Ezdefi()->plugin_url() . '/assets/edd-ezdefi-qrcode.css' );
        wp_enqueue_script( 'edd_ezdefi_checkout', EDD_Ezdefi()->plugin_url() . '/assets/edd-ezdefi-qrcode.js', array( 'jquery', 'jquery-ui-tabs' ), '', true );
        wp_localize_script(
            'edd_ezdefi_checkout',
            'edd_ezdefi_data',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'checkout_url' => edd_get_checkout_uri()
            )
        );
    }
}

new EDD_Ezdefi_Shortcode();