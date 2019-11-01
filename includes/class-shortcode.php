<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EDD_EZPay_Shortcode
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
        $this->init_hooks();
    }

    /** Hook into action and filter */
    private function init_hooks()
    {
        add_filter( 'do_shortcode_tag', array( $this, 'prepend_content_to_shortcode' ), 10, 4 );
    }

    public function prepend_content_to_shortcode( $output, $tag )
    {
        global $edd_receipt_args;

        if ( $tag != 'edd_receipt' ) {
            return $output;
        }

        $payment = get_post( $edd_receipt_args['id'] );

        if( empty( $payment ) ) {
            return $output;
        }

        $status = edd_get_payment_status( $payment, true );

        if( strtolower( $status ) === 'complete' ) {
            return $output;
        }

        $session = edd_get_purchase_session();

        $json = array(
            'uoid' => edd_get_payment_number( $payment->ID ),
            'subtotal' => $session['subtotal'],
            'currency' => edd_get_payment_currency_code( $payment->ID )
        );

        $redirect = array(
            'timeout' => edd_get_checkout_uri()
        );

        $ezpay_currency = edd_ezpay_get_currency();

        $ezpay_payment = edd_ezpay_get_payment_session();

        if($ezpay_payment && !empty($ezpay_payment)) {
            $selected_symbol = $ezpay_payment['data']['_doc']['currency'];
        } else {
            $selected_symbol = $session['post_data']['edd_ezpay_currency_symbol'];
        }

        $selected_currency_key = array_search( $selected_symbol, array_column( $ezpay_currency, 'symbol' ) );

        $selected_currency = $ezpay_currency[$selected_currency_key];

        $this->enqueue_scripts();

        ob_start(); ?>
        <div id="edd-ezpay-qrcode-section">
            <script type="application/json" id="payment-data"><?php echo json_encode( $json ); ?></script>
            <script type="application/json" id="redirect-url"><?php echo json_encode( $redirect ); ?></script>
            <?php if($ezpay_payment && !empty($ezpay_payment)) : ?>
                <script type="application/json" id="ezpay-payment"><?php echo json_encode( $ezpay_payment['data'] ); ?></script>
            <?php endif; ?>
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
                        <input <?php echo ($c['symbol'] === $selected_symbol) ? 'checked' : ''; ?> type="radio" name="currency" id="<?php echo $c['symbol']; ?>">
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
            <div class="ezpay-payment">
            </div>
            <button class="submitBtn" style="<?php echo ($ezpay_payment) ? 'display: none' : ''; ?> ">Confirm</button>
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
        wp_enqueue_style( 'edd_ezpay_checkout', EDD_EZPay()->plugin_url() . '/assets/edd-ezpay-qrcode.css' );
        wp_enqueue_script( 'edd_ezpay_checkout', EDD_EZPay()->plugin_url() . '/assets/edd-ezpay-qrcode.js', array('jquery'), '', true );
        wp_localize_script(
            'edd_ezpay_checkout',
            'edd_ezpay_data',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' )
            )
        );
    }
}