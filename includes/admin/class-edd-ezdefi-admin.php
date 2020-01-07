<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezdefi_Admin
{
	/**
	 * EDD_Ezdefi_Admin constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ), 5 );

		add_action( 'admin_enqueue_scripts', array( $this, 'load_styles_scripts' ) );
	}

	/**
	 * Initializes class
	 */
	public function init()
	{
		require_once edd_ezdefi()->plugin_path() . '/includes/admin/class-edd-ezdefi-admin-settings.php';

		require_once edd_ezdefi()->plugin_path() . '/includes/admin/class-edd-ezdefi-admin-notices.php';
	}

	/**
	 * Load needed CSS and JS file
	 */
	public function load_styles_scripts()
	{
		wp_register_script( 'edd_ezdefi_jquery_tiptip', edd_ezdefi()->plugin_url() . '/assets/jquery.tipTip.js', array('jquery') );
		wp_register_style( 'edd_ezdefi_select2', edd_ezdefi()->plugin_url() . '/assets/select2.min.css' );
		wp_register_script( 'edd_ezdefi_select2', edd_ezdefi()->plugin_url() . '/assets/select2.min.js', array('jquery') );
		wp_register_script( 'edd_ezdefi_jquery_validation', edd_ezdefi()->plugin_url() . '/assets/jquery.validate.min.js', array('jquery') );
		wp_register_style( 'edd_ezdefi_admin', edd_ezdefi()->plugin_url() . '/assets/edd-ezdefi-admin.css' );
		wp_register_script( 'edd_ezdefi_admin', edd_ezdefi()->plugin_url() . '/assets/edd-ezdefi-admin.js', array( 'jquery', 'jquery-ui-sortable', 'jquery-tiptip' ) );
		wp_localize_script( 'edd_ezdefi_admin', 'edd_ezdefi_data',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' )
			)
		);
	}
}

new EDD_Ezdefi_Admin();