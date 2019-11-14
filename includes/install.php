<?php

defined( 'ABSPATH' ) or exit;

function edd_ezpay_install()
{
	global $wpdb;

	$table_name = $wpdb->prefix . 'edd_ezpay_amount';

	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		price decimal(10,2) NOT NULL,
		amount_id decimal(18,10) NOT NULL,
		amount_decimal tinyint(2) NOT NULL,
		amount_valid tinyint(1) NOT NULL,
		currency varchar(10) NOT NULL,
		PRIMARY KEY (amount_id, currency)
		) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

register_activation_hook( EDD_EZPAY_FILE, 'edd_ezpay_install' );