<?php
/**
 * Plugin Name: EZPay for Easy Digital Downloads
 * Description: EZPay Gateway integration for Easy Digital Downloads
 * Version: 1.0
 * Author: Nexty Platform
 * Author URI: https://nexty.io
 * License: GPL
 * Text Domain: edd-ezpay
 */

defined( 'ABSPATH' ) || exit;

if( ! defined( 'EDD_EZPAY_FILE' ) ) {
    define( 'EDD_EZPAY_FILE', __FILE__ );
}

if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
    return;
}

if( ! class_exists( 'EDD_EZPay' ) ) {
    include_once dirname( __FILE__ ) . '/includes/class-edd-ezpay.php';
}

function EDD_EZPay() {
    $instance = EDD_EZPay_Class::instance();
    $instance->init();

    return $instance;
}

/** Load plugin after Easy Digital Downloads */
add_action( 'plugin_loaded', 'EDD_EZPay' );