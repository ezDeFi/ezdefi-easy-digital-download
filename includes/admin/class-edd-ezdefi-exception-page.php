<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezdefi_Exception_Page
{
	public function __construct()
	{
		add_action( 'admin_menu', array( $this, 'add_ezdefi_exception_page_link' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
	}

	public function register_scripts()
	{
		wp_register_style( 'edd_ezdefi_select2', edd_ezdefi()->plugin_url() . '/assets/select2.min.css' );
		wp_register_script( 'edd_ezdefi_select2', edd_ezdefi()->plugin_url() . '/assets/select2.min.js', array( 'jquery' ), EDD_EZDEFI_VERSION, true );
		wp_register_style( 'edd_ezdefi_assign', edd_ezdefi()->plugin_url() . '/assets/edd-ezdefi-assign.css' );
		wp_register_script( 'edd_ezdefi_assign', edd_ezdefi()->plugin_url() . '/assets/edd-ezdefi-assign.js', array( 'jquery' ), EDD_EZDEFI_VERSION, true );
	}

	public function add_ezdefi_exception_page_link()
	{
		add_submenu_page( 'edit.php?post_type=download', __( 'ezDeFi Exception Management', 'edd-ezdefi' ), __( 'ezDeFi Exception', 'edd-ezdefi' ), 'manage_shop_settings', 'edd-ezdefi-exception', array( $this, 'ezdefi_exception_page' ) );

	}

	public function ezdefi_exception_page()
	{
		wp_enqueue_style( 'edd_ezdefi_select2' );
		wp_enqueue_script( 'edd_ezdefi_select2' );
		wp_enqueue_style( 'edd_ezdefi_assign' );
		wp_enqueue_script( 'edd_ezdefi_assign' );
		wp_localize_script( 'edd_ezdefi_assign', 'edd_ezdefi_data',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);

		include_once dirname( __FILE__ ) . '/views/html-admin-page-ezdefi-exception.php';
	}
}

new EDD_Ezdefi_Exception_Page();