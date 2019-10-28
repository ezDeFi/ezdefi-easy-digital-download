<?php

class Test_EDD_EZPay_Front_End extends WP_UnitTestCase
{
    public $object;

    public function setUp()
    {
        parent::setUp();

        require_once 'includes/class-front-end.php';

        $this->object = EDD_EZPay_Front_End::instance();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function test_init()
    {
        $this->assertFalse( has_action( 'edd_ezpay_cc_form', '__return_false' ) );

        $this->assertFalse( has_action( 'wp_enqueue_scripts', array(
            $this->object, 'enqueue_scripts'
        ) ) );

        $this->assertFalse( has_filter( 'edd_purchase_form_after_cc_form', array(
            $this->object, 'currency_select_after_cc_form'
        ) ) );

        $this->object->init();

        $this->assertNotFalse( has_filter( 'edd_purchase_form_after_cc_form', array(
            $this->object, 'currency_select_after_cc_form'
        ) ) );

        $this->assertNotFalse( has_action( 'wp_enqueue_scripts', array(
            $this->object, 'enqueue_scripts'
        ) ) );

        $this->assertNotFalse( has_action( 'edd_ezpay_cc_form', '__return_false' ) );
    }

    public function test_enqueue_scripts()
    {
        add_filter( 'edd_is_checkout', '__return_true' );

        $this->object->init();

        $this->assertFalse( wp_style_is( 'edd_ezpay_checkout', 'enqueued' ) );

        $this->object->enqueue_scripts();

        $this->assertTrue( wp_style_is( 'edd_ezpay_checkout', 'enqueued' ) );
    }
}