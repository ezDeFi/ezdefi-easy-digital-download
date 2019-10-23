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
        $this->remove_cc_form();
        $this->add_ezpay_currency_select();
        $this->init_hooks();
    }

    /** Remove CC Form */
    public function remove_cc_form()
    {
        add_action( 'edd_ezpay_cc_form', '__return_false' );
    }

    /** Add Ezpay currency selectiom after cc form */
    public function add_ezpay_currency_select()
    {
        add_action( 'edd_purchase_form_after_cc_form', array( $this, 'currency_select_after_cc_form' ) );
    }

    /** Ezpay currency selection callback */
    public function currency_select_after_cc_form()
    {
        ob_start(); ?>
        <fieldset id="ezpay-currency-select">
            <legend>Select Currency</legend>
            <?php $currency = edd_ezpay_get_currency(); ?>
            <?php foreach( $currency as $c ) : ?>
                <div class="currency-select__item">
                    <input required type="radio" name="edd_ezpay_currency_symbol" id="ezpay_currency_<?php echo isset( $c['symbol'] ) ? $c['symbol'] : ''; ?>" value="<?php echo isset( $c['symbol'] ) ? $c['symbol'] : ''; ?>">
                    <label for="ezpay_currency_<?php echo isset( $c['symbol'] ) ? $c['symbol'] : ''; ?>">
                        <?php if( isset( $c['logo'] ) ) : ?>
                            <img width="24" src="<?php echo $c['logo']; ?>" alt="">
                        <?php endif; ?>
                        <span><?php echo (isset($c['name'])) ? $c['name'] : ''; ?></span>
                        <?php if( isset($c['discount'] ) && intval( $c['discount'] > 0) ) : ?>
                            <span>(<?php echo $c['discount']; ?>% discount)</span>
                        <?php endif; ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </fieldset>
        <?php echo ob_get_clean();
    }

    /** Hook into action and filter */
    private function init_hooks()
    {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /** Load needed CSS and JS file */
    public function enqueue_scripts()
    {
        wp_enqueue_style( "edd_ezpay_frontend_style", EDD_EZPay()->plugin_url() . '/assets/frontend.css' );
        wp_enqueue_script( "edd_ezpay_frontend_script", EDD_EZPay()->plugin_url() . '/assets/frontend.js', array('jquery'), '', FALSE );
    }

    private function get_list_coin()
    {
        $json = file_get_contents(EZPAY_PLUGIN_PATH . 'assets/list-coin.json');
        $list = json_decode($json);

        return $list;
    }

    private function get_priority_coin()
    {
        return array_slice($this->get_list_coin(), 0, 5);
    }

    private function get_other_coin()
    {
        return array_slice($this->get_list_coin(), 5, -1);
    }
}