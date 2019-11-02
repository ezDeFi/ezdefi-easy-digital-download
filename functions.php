<?php

/** Get instance of EDD_Ezpay class */
function edd_ezpay() {
	return EDD_Ezpay::instance();
}

/** Get accepted currency */
function edd_ezpay_get_currency()
{
	return edd_get_option( 'ezpay_currency' );
}