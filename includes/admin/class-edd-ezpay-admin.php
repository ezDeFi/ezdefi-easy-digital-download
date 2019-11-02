<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezpay_Admin
{
	/** EDD_Ezpay_Admin constructor */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ), 5 );

		add_action( 'admin_enqueue_scripts', array( $this, 'load_styles_scripts' ) );

		add_filter( 'plugin_action_links', array( $this, 'add_action_links' ), 10, 2 );
	}

	/** Initializes class */
	public function init()
	{
		require_once edd_ezpay()->plugin_path() . '/includes/admin/class-edd-ezpay-admin-settings.php';

		require_once edd_ezpay()->plugin_path() . '/includes/admin/class-edd-ezpay-admin-notices.php';
	}

	/** Load needed CSS and JS file */
	public function load_styles_scripts()
	{
		wp_enqueue_script( 'edd_ezpay_jquery_tiptip', edd_ezpay()->plugin_url() . '/assets/jquery.tipTip.js', array('jquery') );
		wp_enqueue_style( 'edd_ezpay_select2', edd_ezpay()->plugin_url() . '/assets/select2.min.css' );
		wp_enqueue_script( 'edd_ezpay_select2', edd_ezpay()->plugin_url() . '/assets/select2.min.js', array('jquery') );
		wp_enqueue_script( 'edd_ezpay_jquery_validation', edd_ezpay()->plugin_url() . '/assets/jquery.validate.min.js', array('jquery') );
		wp_enqueue_style( 'edd_ezpay_admin', edd_ezpay()->plugin_url() . '/assets/edd-ezpay-admin.css' );
		wp_enqueue_script( 'edd_ezpay_admin', edd_ezpay()->plugin_url() . '/assets/edd-ezpay-admin.js', array( 'jquery', 'jquery-ui-sortable', 'jquery-tiptip' ) );
		wp_localize_script( 'edd_ezpay_admin', 'edd_ezpay_data',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' )
			)
		);
	}

	/** Add action links in the plugin's row */
	public function add_action_links( $links )
	{
		$plugin_links = array(
			'<a href="admin.php?page=edd-settings&tab=gateways&section=ezpay">' . esc_html__( 'Settings', 'edd-ezpay' ) . '</a>',
			'<a href="https://ezpay.io">' . esc_html__( 'Support', 'edd-ezpay' ) . '</a>'
		);

		return array_merge( $plugin_links, $links );
	}
}

new EDD_Ezpay_Admin();