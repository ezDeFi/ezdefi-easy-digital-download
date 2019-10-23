<?php

class Test_EDD_EZPay_Class extends WP_UnitTestCase
{
    public $object;

    public function setUp()
    {
        parent::setUp();

        require_once 'includes/class-edd-ezpay.php';

        $this->object = EDD_EZPay_Class::instance();
    }

    public function test_init()
    {
        $this->assertFalse( has_filter( 'edd_payment_gateways', array(
            $this->object, 'register_gateway'
        ) ) );
        $this->assertFalse( has_action( 'init', array(
            $this->object, 'listen_for_action'
        ) ) );

        $this->object->init();

        $this->assertNotFalse( has_filter( 'edd_payment_gateways', array(
            $this->object, 'register_gateway'
        ) ) );
        $this->assertNotFalse( has_action( 'init', array(
            $this->object, 'listen_for_action'
        ) ) );
    }

    public function test_register_gateway()
    {
        $default_gateways = [
            'paypal' => [
                'admin_label' => 'Paypal',
                'checkout_label' => 'Paypal'
            ]
        ];

        $result = $this->object->register_gateway($default_gateways);

        $expected = [
            'paypal' => [
                'admin_label' => 'Paypal',
                'checkout_label' => 'Paypal'
            ],
            'ezpay' => [
                'admin_label' =>'EZPay',
                'checkout_label' =>'EZPay'
            ]
        ];

        $this->assertEquals( $expected, $result );
    }

    public function test_do_acion_nextypay_payment()
    {
        $_SERVER['REQUEST_URI'] = '/edd-ezpay/nextypay';

        $action = new MockAction();
        add_action( 'edd_ezpay_nextypay', array( &$action, 'action' ) );

        $this->object->init_hooks();
        $this->object->listen_for_action();

        $this->assertGreaterThan(0, $action->get_call_count());
    }
}