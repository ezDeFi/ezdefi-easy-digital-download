<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EDD_EZPay_Install
{
    protected static $instance = null;

    /** Only one instance of EDD_EZPay_Install can be loaded */
    public static function instance()
    {
        if( is_null( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function init()
    {
        register_activation_hook( EDD_EZPAY_FILE, array( $this, 'install' ) );
    }

    /** Callback handle when install plugin */
    public function install()
    {
        // Clear the permalinks
        flush_rewrite_rules( false );

        // Add EZPay QR Code Page
        wp_insert_post(
            array(
                'post_title'     => __( 'EZPay QR Code', 'edd-ezpay' ),
                'post_content'   => '[ezpay_qrcode]',
                'post_status'    => 'publish',
                'post_author'    => 1,
                'post_type'      => 'page',
                'comment_status' => 'closed'
            )
        );
    }
}