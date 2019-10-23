<?php

function edd_ezpay_get_currency()
{
    return edd_get_option( 'ezpay_currency' );
}

function edd_ezpay_get_qrcode_page_uri()
{
    $page_id = edd_get_option( 'ezpay_qrcode_page', 0 );
    $page_id = absint( $page_id );

    $qrcode_page = get_permalink( $page_id );

    return $qrcode_page;
}