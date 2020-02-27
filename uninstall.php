<?php

defined( 'ABSPATH' ) or exit;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'edd_ezdefi_exception';
$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
$wpdb->query( "DROP EVENT IF EXISTS `edd_ezdefi_clear_exception_table`" );