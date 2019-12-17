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

        $currency = edd_get_option( 'ezdefi_currency' );

	    $to = implode(',', array_map(function ( $item ) {
		    return $item['symbol'];
	    }, $currency ) );

	    $exchanges = $this->api->get_token_exchanges(
		    edd_get_cart_total(),
		    edd_get_currency(),
		    $to
	    );

	    $symbol = $edd_payment->get_meta( '_edd_ezdefi_currency' );
	    $index = array_search( $symbol, array_column( $currency, 'symbol' ) );

	    if( $index === false ) {
	        return;
        }

	    $selected_currency = $currency[$index];

	    $payment_data = array(
		    'uoid' => edd_get_payment_number( $edd_payment->ID ),
		    'ezdefi_payment' => ( $edd_payment->get_meta( '_edd_ezdefi_payment' ) ) ? $edd_payment->get_meta( '_edd_ezdefi_payment' ) : ''
	    );

	    $payment_method = edd_get_option( 'ezdefi_method' );

        $this->enqueue_scripts();

        ob_start(); ?>
        <div id="edd-ezdefi-qrcode-section">
            <script type="application/json" id="payment-data"><?php echo json_encode( $payment_data ); ?></script>
	        <?php include_once dirname( __FILE__ ) . '/views/edd-ezdefi-currency-select.php'; ?>
            <div class="edd-ezdefi-loader"></div>
            <div class="ezdefi-payment-tabs" style="display: none">
                <ul>
	                <?php
	                foreach( $payment_method as $key => $value ) {
		                echo '<li>';
		                switch ($key) {
			                case 'amount_id' :
				                echo '<a href="#'.$key.'" id="tab-'.$key.'"><span class="large-screen">' . __( 'Pay with any crypto wallet', 'edd-ezdefi' ) . '</span><span class="small-screen">' . __( 'Any crypto wallet', 'edd-ezdefi' ) . '</span></a>';
				                break;
			                case 'ezdefi_wallet' :
				                echo '<a href="#'.$key.'" id="tab-'.$key.'" style="background-image: url(' . edd_ezdefi()->plugin_url() . '/assets/ezdefi-icon.png' . ')"><span class="large-screen"> ' . __( 'Pay with ezDeFi wallet', 'edd-ezdefi' ) . '</span><span class="small-screen"> ' . __( 'ezDeFi wallet', 'edd-ezdefi' ) . '</span></a>';
				                break;
		                }
		                echo '</li>';
	                }
	                ?>
                </ul>
	            <?php foreach( $payment_method as $key => $value ) : ?>
                    <div id="<?php echo $key;?>" class="ezdefi-payment-panel"></div>
	            <?php endforeach; ?>
            </div>
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
	    wp_enqueue_style( 'edd_ezdefi_qrcode', EDD_Ezdefi()->plugin_url() . '/assets/edd-ezdefi-qrcode.css' );
        wp_enqueue_script( 'edd_ezdefi_qrcode', EDD_Ezdefi()->plugin_url() . '/assets/edd-ezdefi-qrcode.js', array( 'jquery', 'jquery-ui-tabs' ), '', true );
        wp_localize_script(
            'edd_ezdefi_qrcode',
            'edd_ezdefi_data',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
            )
        );
    }
}

new EDD_Ezdefi_Shortcode();