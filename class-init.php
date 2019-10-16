<?php

class EDD_EZPay_Init
{
    private $domain = 'edd-ezpay';

    public function init()
    {
        $this->addFilter();
        $this->add_action();
    }

    private function addFilter()
    {
        add_filter( 'edd_payment_gateways', array( $this, 'register_gateway' ), 1, 1 );
    }

    private function add_action()
    {
        add_action( 'init', array( $this, 'listen_for_action' ), 1, 1 );
    }

    public function register_gateway($gateways)
    {
        $gateways['ezpay'] = array(
            'admin_label' => __( 'EZPay', $this->domain ),
            'checkout_label' => __( 'EZPay', $this->domain )
        );

        return $gateways;
    }

    public function listen_for_action()
    {
        require_once EZPAY_PLUGIN_PATH . 'class-listener.php';
        $listener = new EDD_EZPay_Listener( EZPAY_PLUGIN_API_PATH );
        $action = $listener->listen( $_SERVER['REQUEST_URI'] );

        if( !empty( $action ) ) {
            do_action( 'edd_ezpay_' . $action );
        }
    }
}