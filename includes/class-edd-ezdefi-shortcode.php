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

        if( empty( $coin_id = $edd_payment->get_meta( '_edd_ezdefi_coin' ) ) ) {
            return $output;
        }

        $status = edd_get_payment_status( $edd_payment, true );

        if( strtolower( $status ) === 'complete' ) {
            return $output;
        }

        $website_config = $this->api->get_website_config();

        if ( is_null( $website_config ) ) {
            return;
        }

        $coins = $website_config['coins'];

        $selected_currency = null;

        foreach ( $coins as $key => $coin ) {
            if ( $coin['_id'] == $coin_id ) {
                $selected_currency = $coins[$key];
            }
        }

        if( is_null( $selected_currency ) ) {
            $selected_currency = $coins[0];
        }

        $to = implode(',', array_map( function ( $coin ) {
            return $coin['token']['symbol'];
        }, $coins ) );

        $exchanges = $this->api->get_token_exchanges(
            edd_get_cart_total(),
            edd_get_currency(),
            $to
        );

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
                    if( $website_config['website']['payAnyWallet'] == true ) {
                        echo '<li>';
                        echo '<a href="#amount_id" id="tab-amount_id><span class="large-screen">' . __( 'Pay with any crypto wallet', 'woocommerce-gateway-ezdefi' ) . '</span><span class="small-screen">' . __( 'Any crypto wallet', 'woocommerce-gateway-ezdefi' ) . '</span></a>';
                        echo '</li>';
                    }

                    if( $website_config['website']['payEzdefiWallet'] == true ) {
                        echo '<li>';
                        echo '<a href="#ezdefi_wallet" id="tab-ezdefi_wallet" style="background-image: url(' . edd_ezdefi()->plugin_url() . '/assets/ezdefi-icon.png'.')"><span class="large-screen"> ' . __( 'Pay with ezDeFi wallet', 'woocommerce-gateway-ezdefi' ) . '</span><span class="small-screen" style="background-image: url('.edd_ezdefi()->plugin_url().'/assets/ezdefi-icon.png'.')"> ' . __( 'ezDeFi wallet', 'woocommerce-gateway-ezdefi' ) . '</span></a>';
                        echo '</li>';
                    }
                    ?>
                </ul>
                <?php
                    if( $website_config['website']['payAnyWallet'] == true ) {
                        echo '<div id="amount_id" class="ezdefi-payment-panel"></div>';
                    }

                    if( $website_config['website']['payEzdefiWallet'] == true ) {
                        echo '<div id="ezdefi_wallet" class="ezdefi-payment-panel"></div>';
                    }
                ?>
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
        wp_enqueue_script( 'edd_ezdefi_qrcode', EDD_Ezdefi()->plugin_url() . '/assets/edd-ezdefi-qrcode.js', array( 'jquery', 'jquery-ui-tabs', 'clipboard' ), '', true );
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