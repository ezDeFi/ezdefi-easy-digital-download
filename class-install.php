<?php

class EDD_EZPay_Install
{
    private $domain = 'edd-ezpay';

    public function init()
    {
        register_activation_hook( EZPAY_PLUGIN_FILE, array( $this, 'edd_ezpay_install' ) );
    }

    public function edd_ezpay_install()
    {
        // Clear the permalinks
        flush_rewrite_rules( false );

        // Add EZPay QR Code Page
        wp_insert_page(
            array(
                'post_title'     => __( 'EZPay QR Code', $this->domain ),
                'post_content'   => '[ezpay_qrcode]',
                'post_status'    => 'publish',
                'post_author'    => 1,
                'post_type'      => 'page',
                'comment_status' => 'closed'
            )
        );
    }
}