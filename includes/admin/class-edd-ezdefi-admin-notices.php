<?php

defined( 'ABSPATH' ) or exit;

class EDD_Ezdefi_Admin_Notices
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

        if( ! array_key_exists( 'ezdefi', edd_get_enabled_payment_gateways() ) ) {
            return;
        }

        $this->check_gateway_config();

        $this->render_notices();
    }

    protected function check_gateway_config()
    {
        $setting_link = $this->get_setting_link();

        $api_url = edd_get_option('ezdefi_api_url');

        $api_key = edd_get_option('ezdefi_api_key');

        $public_key = edd_get_option( 'ezdefi_public_key' );

        if( empty( $api_url ) || empty( $api_key ) || empty( $public_key ) ) {
            $this->notices[] = sprintf( __( 'Ezdefi is almost ready. <a href="%s">Click here</a> to finish setting up this integration.', 'edd-ezdefi' ), $setting_link );
        }
    }

    protected function render_notices()
    {
        foreach( $this->notices as $notice ) {
            echo '<div class="notice notice-error is-dismissible"><p>' . wp_kses( $notice, array( 'a' => array( 'href' => array(), 'target' => array() ) ) )  . '</p></div>';
        }
    }

    protected function get_setting_link() {
        return admin_url( 'admin.php?page=edd-settings&tab=gateways&section=ezdefi' );
    }
}

new EDD_Ezdefi_Admin_Notices();