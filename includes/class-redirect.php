<?php

class EDD_EZPay_Redirect
{
    protected static $instance = null;

    /** Only one instance of EDD_EZPay_Redirect can be loaded */
    public static function instance()
    {
        if( is_null( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function init()
    {
        add_action( 'edd_ezpay_success', array( $this, 'edd_ezpay_payment_success' ) );
        add_action( 'edd_ezpay_timeout', array( $this, 'edd_ezpay_payment_timeout' ) );
    }

    public function success()
    {
        edd_clear_errors();

        $uri = edd_get_success_page_uri();

        $this->redirect( $uri );
    }

    public function timeout()
    {
        edd_set_error( 'ezpay_payment_timeout', __( 'EZPay Timeout', 'edd-ezpay' ) );

        $uri = edd_get_checkout_uri();

        $uri = $uri . '?payment-mode=ezpay';

        $this->redirect( $uri );
    }

    public function redirect($uri)
    {
        wp_redirect( $uri );

        wp_die();
    }
}