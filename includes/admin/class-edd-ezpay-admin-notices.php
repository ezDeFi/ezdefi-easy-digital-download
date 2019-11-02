<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezpay_Admin_Notices
{
	protected $notices = array();

	public function __construct()
	{
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	public function admin_notices()
	{
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if( ! array_key_exists( 'ezpay', edd_get_enabled_payment_gateways() ) ) {
			return;
		}

		$this->check_gateway_config();

		$this->check_currency_config();

		$this->render_notices();
	}

	protected function check_gateway_config()
	{
		$setting_link = $this->get_setting_link();

		$api_url = edd_get_option('ezpay_api_url');

		if( ! isset( $api_url ) || empty( $api_url ) ) {
			$this->notices[] = sprintf( __( 'Ezpay is almost ready. To get started, <a href="%s">set your gateway api url</a>.', 'edd-ezpay' ), $setting_link );
		}

		$api_key = edd_get_option('ezpay_api_key');

		if( ! isset( $api_key ) || empty( $api_key ) ) {
			$this->notices[] = sprintf( __( 'Ezpay is almost ready. To get started, <a href="%s">set your gateway api key</a>.', 'edd-ezpay' ), $setting_link );
		}
	}

	protected function check_currency_config()
	{
		$setting_link = $this->get_setting_link();

		$currency = edd_get_option( 'ezpay_currency' );
		
		if( ! isset( $currency ) || empty( $currency ) ) {
			$this->notices[] = sprintf( __( 'Ezpay is almost ready. To get started, <a href="%s">set accepted currency</a>.', 'edd-ezpay' ), $setting_link );
		}
	}

	protected function render_notices()
	{
		foreach( $this->notices as $notice ) {
			echo '<div class="error"><p>' . wp_kses( $notice, array( 'a' => array( 'href' => array(), 'target' => array() ) ) )  . '</p></div>';
		}
	}

	protected function get_setting_link() {
		return admin_url( 'admin.php?page=edd-settings&tab=gateways&section=ezpay' );
	}
}

new EDD_Ezpay_Admin_Notices();