<?php

/**
 * Get instance of EDD_Ezdefi class
 */
function edd_ezdefi() {
	return EDD_Ezdefi::instance();
}

/**
 * Get accepted currency
 */
function edd_ezdefi_get_currency()
{
	return edd_get_option( 'ezdefi_currency' );
}