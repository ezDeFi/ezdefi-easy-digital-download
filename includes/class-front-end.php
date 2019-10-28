<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EDD_EZPay_Front_End
{
    protected static $instance = null;

    /** Only one instance of EDD_EZPay_Class can be loaded */
    public static function instance()
    {
        if( is_null( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function init()
    {
        add_action( 'edd_ezpay_cc_form', '__return_false' );

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        add_filter( 'edd_purchase_form_after_cc_form', array( $this, 'currency_select_after_cc_form' ) );
    }

    /** Load needed CSS and JS file */
    public function enqueue_scripts()
    {
        if( edd_is_checkout() ) {
            wp_enqueue_style('edd_ezpay_checkout', EDD_EZPay()->plugin_url() . '/assets/edd-ezpay-checkout.css');
        }
    }

    public function currency_select_after_cc_form()
    {
        ob_start(); ?>
        <fieldset id="ezpay-currency-select">
            <legend>Select Currency</legend>
            <?php $currency = edd_ezpay_get_currency(); ?>
            <?php foreach( $currency as $c ) : ?>
                <div class="ezpay-currency">
                    <input required type="radio" name="edd_ezpay_currency_symbol" id="<?php echo $c['symbol']; ?>" value="<?php echo $c['symbol']; ?>">
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
                                    <span class="tooltip"><?php echo $c['desc']; ?></span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </label>
                </div>
            <?php endforeach; ?>
        </fieldset>
        <?php echo ob_get_clean();
    }
}