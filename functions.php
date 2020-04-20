<?php

/**
 * Get instance of EDD_Ezdefi class
 */
function edd_ezdefi() {
	return EDD_Ezdefi::instance();
}

function edd_ezdefi_is_pay_any_wallet( $payment_data )
{
    if( ! is_array( $payment_data ) ) {
        return false;
    }

    return ( isset( $payment_data['amountId'] ) && $payment_data['amountId'] = true );
}

function edd_ezdefi_sanitize_float_value( $value )
{
    $notation = explode('E', strtoupper( $value ) );

    if(count($notation) === 2){
        $exp = abs(end($notation)) + strlen($notation[0]);
        $decimal = number_format($value, $exp);
        $value = rtrim($decimal, '.0');
    }

    return $value;
}

function edd_ezdefi_sanitize_uoid( $uoid )
{
    return substr( $uoid, 0, strpos( $uoid,'-' ) );
}