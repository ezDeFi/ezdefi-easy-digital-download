<?php

function edd_ezpay_get_currency()
{
    return edd_get_option( 'ezpay_currency' );
}

function edd_ezpay_get_checkout_page_uri()
{
    $page_id = edd_get_option( 'ezpay_checkout_page', 0 );
    $page_id = absint( $page_id );

    $checkout_page = get_permalink( $page_id );

    return $checkout_page;
}

function edd_ezpay_get_payment_session()
{
    return EDD()->session->get( 'edd_ezpay_payment' );
}