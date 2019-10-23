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
        add_shortcode( 'ezpay_qrcode', array( $this, 'ezpay_qrcode_template' ) );
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /** Load needed CSS and JS file */
    public function enqueue_scripts()
    {
        global $post;

        if( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'ezpay_qrcode' ) ) {
            wp_enqueue_script( 'edd_ezpay_qrcode_js', EDD_EZPay()->plugin_url() . '/assets/qrcode.js', array('jquery'), '', true );
            wp_localize_script(
                'edd_ezpay_qrcode_js',
                'edd_ezpay_data',
                array(
                    'ajax_url' => admin_url( 'admin-ajax.php' )
                )
            );
        }
    }

    /** Output */
    public function ezpay_qrcode_template()
    {
        $ezpay_payment = EDD()->session->get( 'edd_ezpay_payment' );

        if( !$ezpay_payment || empty( $ezpay_payment ) ) {
            echo "<script>window.location.replace('" . edd_get_checkout_uri() . "')</script>";
            return;
        }

        $payment_data = $ezpay_payment['data'];

        $json = array(
            '_doc' => $payment_data['_doc'],
            'qr' => $payment_data['qr']
        );

        $redirect = array(
            'success' => edd_get_success_page_uri(),
            'timeout' => edd_get_checkout_uri()
        );
        ob_start(); ?>
        <script type="application/json" data-payment-data><?php echo json_encode( $json ); ?></script>
        <script type="application/json" data-redirect-url><?php echo json_encode( $redirect ); ?></script>
        <div id="edd_ezpay_qrcode_page" data-content-wrapper>
            Loading...
        </div>
        <?php
        echo ob_get_clean();
    }
}