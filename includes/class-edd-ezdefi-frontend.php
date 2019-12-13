<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezdefi_Frontend
{
    public $api;

	/** EDD_Ezdefi_Frontend constructor */
    public function __construct()
    {
        $this->api = new EDD_Ezdefi_Api();

        add_action( 'edd_ezdefi_cc_form', array( $this, 'currency_select_after_cc_form' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /** Load needed CSS and JS file */
    public function enqueue_scripts()
    {
        if( edd_is_checkout() ) {
            wp_enqueue_style('edd_ezdefi_checkout', edd_ezdefi()->plugin_url() . '/assets/edd-ezdefi-checkout.css');
	        wp_enqueue_script('edd_ezdefi_checkout', edd_ezdefi()->plugin_url() . '/assets/edd-ezdefi-checkout.js');
        }
    }

    /** Add currency select after cc form */
    public function currency_select_after_cc_form()
    {
        $currency = edd_get_option( 'ezdefi_currency' );
	    $selected_currency = array();

	    $to = implode(',', array_map(function ( $currency ) {
		    return $currency['symbol'];
	    }, $currency ) );

	    $exchanges = $this->api->get_token_exchanges(
		    edd_get_cart_total(),
		    edd_get_currency(),
		    $to
	    );

	    ob_start(); ?>

        <fieldset id="ezdefi-currency-select">
            <legend><?php _e( 'Select Currency', 'edd-ezdefi' ); ?></legend>
            <input type="hidden" name="edd_ezdefi_currency_symbol" id="edd_ezdefi_currency_symbol">
            <?php include_once dirname( __FILE__ ) . '/views/edd-ezdefi-currency-select.php'; ?>
        </fieldset>

        <?php echo ob_get_clean();
    }
}

new EDD_Ezdefi_Frontend();