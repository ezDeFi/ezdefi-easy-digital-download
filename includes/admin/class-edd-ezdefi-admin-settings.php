<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezdefi_Admin_Settings
{
	/**
     * EDD_Ezdefi_Admin_Settings constructor.
     */
	public function __construct() {
		add_filter( 'edd_settings_sections_gateways', array( $this, 'register_settings_section' ) );
		add_filter( 'edd_settings_gateways', array( $this, 'register_settings' ) );
	}

	/**
     * Ezdefi settings section callback
     */
	public function register_settings_section($sections)
	{
		$sections['ezdefi'] = __( 'ezDeFi', 'edd-ezdefi' );

		return $sections;
	}

	/**
     * Ezdefi settings callback
     */
	public function register_settings($gateway_settings)
	{
		$ezdefi_settings = array(
			'ezdefi_settings' => array(
				'id'   => 'ezdefi_settings',
				'name' => '<strong>' . __( 'ezDeFi Settings', 'edd-ezdefi' ) . '</strong>',
				'type' => 'header',
			),
			'ezdefi_api_url' => array(
				'id' => 'ezdefi_api_url',
				'name' => __( 'API Url', 'edd-ezdefi' ),
				'desc' => '',
				'type' => 'text',
				'size' => 'regular',
				'class' => 'ezdefi_api_url'
			),
			'ezdefi_api_key' => array(
				'id' => 'ezdefi_api_key',
				'name' => __( 'API Key', 'edd-ezdefi' ),
				'desc' => sprintf( __( '<a target="_blank" href="%s">Register to get API Key</a>', 'edd-ezdefi' ), 'https://merchant.ezdefi.com/register?utm_source=edd-download' ),
				'type' => 'text',
				'size' => 'regular',
				'class' => 'ezdefi_api_key'
			),
            'ezdefi_public_key' => array(
                'id' => 'ezdefi_public_key',
                'name' => __( 'Website ID', 'edd-ezdefi' ),
                'desc' => '',
                'type' => 'text',
                'size' => 'regular',
                'class' => 'ezdefi_public_key'
            )
		);

		$gateway_settings['ezdefi'] = $ezdefi_settings;

		return $gateway_settings;
	}
}

new EDD_Ezdefi_Admin_Settings();